<?php

namespace App\Support\Payments;

use App\Models\Order;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class KgpQrGateway implements ThaiQrGateway
{
    /** KGP returns HTTP 417 with these codes when the QR can no longer be paid. */
    private const TERMINAL_CODES = ['2001', '2002', '1016'];

    /** KGP returns HTTP 417 with these codes while the customer has not paid yet. */
    private const NOT_PAID_CODES = ['1008', '1017', '1028'];

    public function provider(): string
    {
        return 'kgp';
    }

    public function createQr(Payment $payment, Order $order): GatewayQr
    {
        $expireSeconds = min(86400, max(60, (int) config('payment.kgp.qr_expire_seconds', 600)));

        $body = [
            'created_datetime' => now()->utc()->format('Y-m-d\TH:i:s\Z'),
            'amount' => round((float) $payment->amount, 2),
            'currency' => 'THB',
            'description' => Str::limit('Order '.$order->order_no, 250, ''),
            'source_type' => 'ThaiQR',
            'reference_order' => $payment->reference,
            'ref_1' => $this->reference($order->order_no),
            'qr_expire_time' => $expireSeconds,
        ];

        $response = $this->send(fn (PendingRequest $http) => $http->post('/api/v2/qr', $body));
        $data = $response->json() ?? [];

        if (! $response->successful() || $this->status($data) !== 'success' || blank($data['qr_id'] ?? null)) {
            throw new GatewayException(
                (string) ($data['failure_message'] ?? 'สร้าง QR ไม่สำเร็จ'),
                isset($data['failure_code']) ? (string) $data['failure_code'] : null,
                $data,
            );
        }

        $expiresAt = filled($data['expired_datetime'] ?? null)
            ? Carbon::parse((string) $data['expired_datetime'])
            : now()->addSeconds($expireSeconds);

        return new GatewayQr(
            qrId: (string) $data['qr_id'],
            image: $this->imageSource($data),
            expiresAt: $expiresAt,
            accountName: $data['account_name'] ?? null,
            raw: $this->withoutImage($data),
        );
    }

    public function inquire(Payment $payment): GatewayCharge
    {
        try {
            $response = $this->send(fn (PendingRequest $http) => $http->get('/api/v2/charge/'.rawurlencode((string) $payment->reference)));
        } catch (GatewayException $exception) {
            return new GatewayCharge(GatewayCharge::PENDING, failureMessage: $exception->getMessage());
        }

        $data = $response->json() ?? [];
        $code = isset($data['failure_code']) ? (string) $data['failure_code'] : null;

        if (! $response->successful()) {
            $status = in_array($code, self::TERMINAL_CODES, true) ? GatewayCharge::FAILED : GatewayCharge::PENDING;

            return new GatewayCharge(
                $status,
                failureCode: in_array($code, self::NOT_PAID_CODES, true) ? null : $code,
                failureMessage: in_array($code, self::NOT_PAID_CODES, true) ? null : ($data['failure_message'] ?? null),
                raw: $data,
            );
        }

        return $this->chargeFromPayload($data);
    }

    public function cancel(Payment $payment): void
    {
        if (blank($payment->transaction_id)) {
            return;
        }

        try {
            $this->send(fn (PendingRequest $http) => $http->post('/api/v2/qr/cancel/'.rawurlencode((string) $payment->transaction_id)));
        } catch (GatewayException) {
            // The QR expires on its own; a failed cancel must not block a new attempt.
        }
    }

    /**
     * ถามสถานะการคืนเงินจาก KGP (สเปกหัวข้อ 9) ใช้ยืนยัน Refund Notification ที่ไม่มี checksum
     *
     * @return array<string, mixed>|null null เมื่อถาม KGP ไม่สำเร็จ
     */
    public function inquireRefund(string $refundId): ?array
    {
        try {
            $response = $this->send(fn (PendingRequest $http) => $http->get('/api/v2/refund/'.rawurlencode($refundId)));
        } catch (GatewayException) {
            return null;
        }

        $data = $response->json();

        if (! $response->successful() || ! is_array($data) || blank($data['refund_id'] ?? null)) {
            return null;
        }

        return $data;
    }

    public function verifyNotification(array $payload): bool
    {
        $secret = (string) config('payment.kgp.secret_key');
        $checksum = strtolower((string) ($payload['checksum'] ?? ''));

        if ($secret === '' || $checksum === '') {
            return false;
        }

        return hash_equals(self::checksum($payload, $secret), $checksum);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function checksum(array $payload, string $secret): string
    {
        $status = (string) ($payload['status'] ?? $payload['stauts'] ?? '');

        return hash('sha256',
            (string) ($payload['charge_id'] ?? '')
            .number_format((float) ($payload['amount'] ?? 0), 4, '.', '')
            .(string) ($payload['currency'] ?? '')
            .$status
            .(string) ($payload['transaction_state'] ?? '')
            .$secret
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function chargeFromPayload(array $data): GatewayCharge
    {
        $status = $this->status($data);
        $state = strtolower((string) ($data['transaction_state'] ?? ''));

        $result = match (true) {
            $status === 'success' && in_array($state, ['authorized', 'settled'], true) => GatewayCharge::PAID,
            $status === 'fail' || in_array($state, ['voided', 'refunded'], true) => GatewayCharge::FAILED,
            default => GatewayCharge::PENDING,
        };

        return new GatewayCharge(
            status: $result,
            chargeId: isset($data['charge_id']) ? (string) $data['charge_id'] : null,
            amount: isset($data['amount']) ? (float) $data['amount'] : null,
            failureCode: isset($data['failure_code']) ? (string) $data['failure_code'] : null,
            failureMessage: $data['failure_message'] ?? null,
            raw: $data,
        );
    }

    /**
     * @param  callable(PendingRequest): Response  $call
     */
    private function send(callable $call): Response
    {
        $secret = (string) config('payment.kgp.secret_key');

        if ($secret === '') {
            throw new GatewayException('ยังไม่ได้ตั้งค่า KGP_SECRET_KEY');
        }

        $http = Http::baseUrl(rtrim((string) config('payment.kgp.base_url'), '/'))
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('payment.kgp.timeout', 20))
            ->withHeaders([
                'x-api-key' => $secret,
                'x-request-id' => (string) Str::uuid(),
            ]);

        try {
            return $call($http);
        } catch (ConnectionException $exception) {
            throw new GatewayException('เชื่อมต่อ KGP ไม่ได้: '.$exception->getMessage());
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function status(array $data): string
    {
        return strtolower((string) ($data['status'] ?? $data['stauts'] ?? ''));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function imageSource(array $data): ?string
    {
        $base64 = (string) ($data['image_with_base64'] ?? '');

        if ($base64 !== '') {
            return str_starts_with($base64, 'data:') ? $base64 : 'data:image/png;base64,'.$base64;
        }

        $url = (string) ($data['image_url'] ?? '');

        if ($url === '') {
            return null;
        }

        $publicKey = (string) config('payment.kgp.public_key');

        return $publicKey !== '' && ! str_contains($url, 'x-api-key=')
            ? $url.(str_contains($url, '?') ? '&' : '?').'x-api-key='.rawurlencode($publicKey)
            : $url;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withoutImage(array $data): array
    {
        unset($data['image_with_base64']);

        return $data;
    }

    private function reference(string $value): string
    {
        return Str::limit((string) preg_replace('/[^A-Za-z0-9]/', '', $value), 20, '');
    }
}
