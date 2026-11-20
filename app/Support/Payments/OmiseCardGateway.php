<?php

namespace App\Support\Payments;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class OmiseCardGateway implements CardGateway
{
    public function provider(): string
    {
        return 'omise';
    }

    public function publicKey(): ?string
    {
        $key = (string) config('payment.omise.public_key');

        return $key !== '' ? $key : null;
    }

    public function charge(Payment $payment, Order $order, string $cardToken, string $returnUri): GatewayCharge
    {
        $response = $this->send(fn (PendingRequest $http) => $http->post('/charges', [
            'amount' => (int) round((float) $payment->amount * 100),
            'currency' => 'thb',
            'card' => $cardToken,
            'capture' => 'true',
            'return_uri' => $returnUri,
            'description' => 'Order '.$order->order_no,
            'metadata' => [
                'order_no' => $order->order_no,
                'payment_reference' => $payment->reference,
            ],
        ]));

        $data = $response->json() ?? [];

        if (($data['object'] ?? null) === 'error' || ! $response->successful()) {
            throw new GatewayException(
                (string) ($data['message'] ?? 'ตัดบัตรไม่สำเร็จ'),
                isset($data['code']) ? (string) $data['code'] : null,
                $data,
            );
        }

        return $this->chargeFromPayload($data);
    }

    /**
     * ชำระด้วย e-Wallet ผ่าน Omise Source (TrueMoney, ShopeePay, Rabbit LINE Pay, WeChat Pay)
     * ผลคือ charge ที่รอชำระ มี authorize_uri (พาไปแอปหรือหน้าผู้ให้บริการ) หรือ QR ให้สแกน
     *
     * @return array{charge: GatewayCharge, qr_image: ?string, expires_at: ?string, flow: ?string}
     */
    public function chargeSource(Payment $payment, Order $order, string $sourceType, string $returnUri, ?string $ip = null): array
    {
        $params = [
            'amount' => (int) round((float) $payment->amount * 100),
            'currency' => 'thb',
            'source[type]' => $sourceType,
            'return_uri' => $returnUri,
            'description' => 'Order '.$order->order_no,
            'metadata[order_no]' => $order->order_no,
            'metadata[payment_reference]' => $payment->reference,
        ];

        if (str_starts_with($sourceType, 'wechat_pay')) {
            $params['source[ip]'] = $ip ?: '127.0.0.1';
        }

        $response = $this->send(fn (PendingRequest $http) => $http->post('/charges', $params));
        $data = $response->json() ?? [];

        if (($data['object'] ?? null) === 'error' || ! $response->successful()) {
            throw new GatewayException(
                (string) ($data['message'] ?? 'สร้างรายการชำระไม่สำเร็จ'),
                isset($data['code']) ? (string) $data['code'] : null,
                $data,
            );
        }

        return [
            'charge' => $this->chargeFromPayload($data),
            'qr_image' => $this->qrImage((string) data_get($data, 'source.scannable_code.image.download_uri', '')),
            'expires_at' => $data['expires_at'] ?? null,
            'flow' => data_get($data, 'source.flow'),
        ];
    }

    /**
     * ยกเลิก charge ของ e-Wallet ที่ยังไม่จ่าย กันลูกค้าจ่ายรายการเก่าซ้ำ (ไม่สำเร็จก็ไม่เป็นไร)
     */
    public function expire(Payment $payment): void
    {
        if (blank($payment->transaction_id)) {
            return;
        }

        try {
            $this->send(fn (PendingRequest $http) => $http->post('/charges/'.rawurlencode((string) $payment->transaction_id).'/expire'));
        } catch (GatewayException) {
        }
    }

    /**
     * ช่องทางที่บัญชี Omise เปิดใช้ (ชื่อ source type เช่น truemoney_qr) null = ถาม Omise ไม่สำเร็จ
     *
     * @return list<string>|null
     */
    public function capabilities(): ?array
    {
        $publicKey = $this->publicKey();

        if ($publicKey === null) {
            return null;
        }

        return Cache::remember('omise.capability.'.md5($publicKey), now()->addMinutes(10), function () use ($publicKey) {
            try {
                $response = Http::baseUrl(rtrim((string) config('payment.omise.base_url'), '/'))
                    ->acceptJson()
                    ->timeout(10)
                    ->withBasicAuth($publicKey, '')
                    ->get('/capability');
            } catch (ConnectionException) {
                return null;
            }

            if (! $response->successful()) {
                return null;
            }

            return collect($response->json('payment_methods') ?? [])->pluck('name')->filter()->values()->all();
        });
    }

    private function qrImage(string $uri): ?string
    {
        if ($uri === '') {
            return null;
        }

        try {
            $response = Http::timeout(15)->get($uri);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $type = strtok((string) $response->header('Content-Type'), ';') ?: 'image/png';

        return 'data:'.$type.';base64,'.base64_encode($response->body());
    }

    public function inquire(Payment $payment): GatewayCharge
    {
        if (blank($payment->transaction_id)) {
            return new GatewayCharge(GatewayCharge::PENDING);
        }

        try {
            $response = $this->send(fn (PendingRequest $http) => $http->get('/charges/'.rawurlencode((string) $payment->transaction_id)));
        } catch (GatewayException $exception) {
            return new GatewayCharge(GatewayCharge::PENDING, failureMessage: $exception->getMessage());
        }

        $data = $response->json() ?? [];

        if (! $response->successful() || ($data['object'] ?? null) !== 'charge') {
            return new GatewayCharge(GatewayCharge::PENDING, raw: $data);
        }

        return $this->chargeFromPayload($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function chargeFromPayload(array $data): GatewayCharge
    {
        $status = match ((string) ($data['status'] ?? '')) {
            'successful' => GatewayCharge::PAID,
            'failed', 'expired', 'reversed' => GatewayCharge::FAILED,
            default => GatewayCharge::PENDING,
        };

        $failureCode = isset($data['failure_code']) ? (string) $data['failure_code'] : null;

        return new GatewayCharge(
            status: $status,
            chargeId: isset($data['id']) ? (string) $data['id'] : null,
            amount: isset($data['amount']) ? ((int) $data['amount']) / 100 : null,
            authorizeUri: $status === GatewayCharge::PENDING ? ($data['authorize_uri'] ?? null) : null,
            failureCode: $failureCode,
            failureMessage: $this->failureMessage($failureCode, $data['failure_message'] ?? null),
            raw: self::sanitize($data),
        );
    }

    /**
     * เก็บ JSON จาก Omise ไว้ตรวจย้อนหลัง ตัดเลขบัตรเต็มและข้อมูลใหญ่เกินจำเป็น
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function sanitize(array $data): array
    {
        if (isset($data['card']) && is_array($data['card'])) {
            $card = $data['card'];
            $data['card'] = array_filter([
                'object' => $card['object'] ?? null,
                'brand' => $card['brand'] ?? null,
                'last_digits' => $card['last_digits'] ?? null,
                'bank' => $card['bank'] ?? null,
                'financing' => $card['financing'] ?? null,
                'type' => $card['type'] ?? null,
                'expiration_month' => $card['expiration_month'] ?? null,
                'expiration_year' => $card['expiration_year'] ?? null,
            ], fn ($value) => $value !== null);
        }

        if (isset($data['data']) && is_array($data['data']) && ($data['data']['object'] ?? null) === 'charge') {
            $data['data'] = self::sanitize($data['data']);
        }

        // รูป QR ของ e-Wallet เก็บแค่ URI ไม่ดึง binary มาใส่ JSON
        $image = data_get($data, 'source.scannable_code.image');
        if (is_array($image)) {
            data_set($data, 'source.scannable_code.image', array_filter([
                'object' => $image['object'] ?? null,
                'download_uri' => $image['download_uri'] ?? null,
                'type' => $image['type'] ?? null,
            ], fn ($value) => $value !== null));
        }

        return $data;
    }

    private function failureMessage(?string $code, ?string $fallback): ?string
    {
        return match ($code) {
            null => $fallback,
            'insufficient_fund' => 'วงเงินในบัตรไม่พอ กรุณาใช้บัตรอื่น',
            'stolen_or_lost_card' => 'บัตรนี้ถูกแจ้งหายหรือถูกขโมย กรุณาติดต่อธนาคาร',
            'invalid_security_code' => 'รหัส CVV ไม่ถูกต้อง',
            'invalid_account_number', 'invalid_card' => 'หมายเลขบัตรไม่ถูกต้อง',
            'payment_rejected', 'payment_cancelled' => 'รายการถูกปฏิเสธหรือยกเลิก กรุณาติดต่อธนาคาร/ผู้ให้บริการ หรือเลือกวิธีชำระอื่น',
            'failed_fraud_check' => 'รายการไม่ผ่านการตรวจสอบความปลอดภัย กรุณาใช้บัตรอื่น',
            'failed_processing' => 'ประมวลผลไม่สำเร็จ กรุณาลองใหม่',
            'authentication_failure', 'payment_expired' => 'ยืนยันตัวตนกับธนาคารไม่สำเร็จหรือหมดเวลา กรุณาลองใหม่',
            default => $fallback ?? 'ชำระเงินไม่สำเร็จ',
        };
    }

    /**
     * @param  callable(PendingRequest): Response  $call
     */
    private function send(callable $call): Response
    {
        $secret = (string) config('payment.omise.secret_key');

        if ($secret === '') {
            throw new GatewayException('ยังไม่ได้ตั้งค่า OMISE_SECRET_KEY');
        }

        $http = Http::baseUrl(rtrim((string) config('payment.omise.base_url'), '/'))
            ->acceptJson()
            ->asForm()
            ->timeout((int) config('payment.omise.timeout', 30))
            ->withBasicAuth($secret, '');

        try {
            return $call($http);
        } catch (ConnectionException $exception) {
            throw new GatewayException('เชื่อมต่อ Omise ไม่ได้: '.$exception->getMessage());
        }
    }

}
