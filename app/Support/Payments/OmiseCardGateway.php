<?php

namespace App\Support\Payments;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
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
            raw: $this->safe($data),
        );
    }

    private function failureMessage(?string $code, ?string $fallback): ?string
    {
        return match ($code) {
            null => $fallback,
            'insufficient_fund' => 'วงเงินในบัตรไม่พอ กรุณาใช้บัตรอื่น',
            'stolen_or_lost_card' => 'บัตรนี้ถูกแจ้งหายหรือถูกขโมย กรุณาติดต่อธนาคาร',
            'invalid_security_code' => 'รหัส CVV ไม่ถูกต้อง',
            'invalid_account_number', 'invalid_card' => 'หมายเลขบัตรไม่ถูกต้อง',
            'payment_rejected', 'payment_cancelled' => 'ธนาคารปฏิเสธรายการ กรุณาติดต่อธนาคารหรือใช้บัตรอื่น',
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

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function safe(array $data): array
    {
        $card = $data['card'] ?? null;

        return [
            'id' => $data['id'] ?? null,
            'status' => $data['status'] ?? null,
            'amount' => $data['amount'] ?? null,
            'currency' => $data['currency'] ?? null,
            'authorized' => $data['authorized'] ?? null,
            'paid' => $data['paid'] ?? null,
            'failure_code' => $data['failure_code'] ?? null,
            'failure_message' => $data['failure_message'] ?? null,
            'card' => is_array($card) ? [
                'brand' => $card['brand'] ?? null,
                'last_digits' => $card['last_digits'] ?? null,
                'bank' => $card['bank'] ?? null,
            ] : null,
        ];
    }
}
