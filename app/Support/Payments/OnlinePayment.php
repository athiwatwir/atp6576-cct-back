<?php

namespace App\Support\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentLink;
use App\Models\PaymentTransaction;
use App\Support\StudentPurchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OnlinePayment
{
    public function __construct(private readonly StudentPurchase $purchases) {}

    /**
     * ไม่ระบุวิธีชำระ = มีอย่างน้อยหนึ่งวิธีที่อยู่ในโหมดทดสอบ
     */
    public function isFake(?PaymentMethod $method = null): bool
    {
        if ($method !== null) {
            return config('payment.drivers.'.$method->value) !== 'live';
        }

        return $this->isFake(PaymentMethod::ThaiQr) || $this->isFake(PaymentMethod::CreditCard);
    }

    /**
     * @return list<string>
     */
    public function enabledMethods(): array
    {
        return array_values(array_keys(array_filter((array) config('payment.methods', []))));
    }

    public function qrGateway(): ThaiQrGateway
    {
        return $this->isFake(PaymentMethod::ThaiQr) ? app(FakeGateway::class) : app(KgpQrGateway::class);
    }

    public function cardGateway(): CardGateway
    {
        return $this->isFake(PaymentMethod::CreditCard) ? app(FakeGateway::class) : app(OmiseCardGateway::class);
    }

    public function linkFor(Order $order, ?int $createdBy = null): PaymentLink
    {
        $this->assertPayable($order);

        $existing = $order->paymentLinks()
            ->where('status', 'active')
            ->where('expires_at', '>', now()->addMinutes(5))
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        return $order->paymentLinks()->create([
            'token' => Str::random(48),
            'expires_at' => now()->addMinutes(max(5, (int) config('payment.link_ttl_minutes', 60))),
            'status' => 'active',
            'created_by' => $createdBy,
        ]);
    }

    public function findLink(string $token): ?PaymentLink
    {
        return PaymentLink::query()
            ->with(['order.items', 'order.user'])
            ->where('token', $token)
            ->first();
    }

    public function linkUsable(PaymentLink $link): bool
    {
        return $link->status === 'active' && $link->expires_at !== null && $link->expires_at->isFuture();
    }

    public function assertPayable(Order $order): void
    {
        $message = match (true) {
            $order->payment_status === PaymentStatus::Paid->value => 'ออเดอร์นี้ชำระเงินแล้ว',
            $order->payment_status === PaymentStatus::Refunded->value => 'ออเดอร์นี้คืนเงินแล้ว',
            $order->status === OrderStatus::Cancelled->value => 'ออเดอร์นี้ถูกยกเลิก',
            (float) $order->total_amount <= 0 => 'ออเดอร์นี้ไม่มียอดที่ต้องชำระ',
            default => null,
        };

        if ($message !== null) {
            throw ValidationException::withMessages(['order' => $message]);
        }
    }

    public function assertMethodEnabled(PaymentMethod $method): void
    {
        if (! in_array($method->value, $this->enabledMethods(), true)) {
            throw ValidationException::withMessages(['method' => 'ยังไม่เปิดใช้วิธีชำระนี้']);
        }
    }

    public function activeQr(Order $order): ?Payment
    {
        return $order->payments()
            ->where('payment_method', PaymentMethod::ThaiQr->value)
            ->where('status', PaymentStatus::Pending->value)
            ->whereNotNull('transaction_id')
            ->where('expires_at', '>', now()->addSeconds(30))
            ->latest('id')
            ->first();
    }

    public function startThaiQr(Order $order): Payment
    {
        $this->assertMethodEnabled(PaymentMethod::ThaiQr);
        $this->assertPayable($order);

        $gateway = $this->qrGateway();
        $active = $this->activeQr($order);

        $expected = round((float) $order->total_amount + PaymentFees::calculate(PaymentMethod::ThaiQr, (float) $order->total_amount), 2);

        if ($active && $active->provider === $gateway->provider()
            && round((float) $active->amount, 2) === $expected) {
            return $active;
        }

        $this->supersedePending($order);
        $payment = $this->newAttempt($order, PaymentMethod::ThaiQr, $gateway->provider());

        try {
            $qr = $gateway->createQr($payment, $order);
        } catch (GatewayException $exception) {
            $this->markFailed($payment, $exception->failureCode, $exception->getMessage(), 'qr.create_failed', $exception->raw);

            throw ValidationException::withMessages(['method' => 'สร้าง QR ไม่สำเร็จ: '.$exception->getMessage()]);
        }

        $payment->update([
            'transaction_id' => $qr->qrId,
            'expires_at' => $qr->expiresAt,
            'metadata' => [
                ...($payment->metadata ?? []),
                'qr_image' => $qr->image,
                'account_name' => $qr->accountName,
            ],
        ]);

        $this->log($payment, 'qr.created', 'pending', $qr->qrId, $qr->raw);

        return $payment->refresh();
    }

    /**
     * @param  callable(Payment): string  $returnUri
     */
    public function chargeCard(Order $order, string $cardToken, callable $returnUri): Payment
    {
        $this->assertMethodEnabled(PaymentMethod::CreditCard);
        $this->assertPayable($order);

        $gateway = $this->cardGateway();
        $this->supersedePending($order);
        $payment = $this->newAttempt($order, PaymentMethod::CreditCard, $gateway->provider());

        try {
            $charge = $gateway->charge($payment, $order, $cardToken, $returnUri($payment));
        } catch (GatewayException $exception) {
            $this->markFailed($payment, $exception->failureCode, $exception->getMessage(), 'card.charge_failed', $exception->raw);

            return $payment->refresh();
        }

        $payment->update(array_filter([
            'transaction_id' => $charge->chargeId,
            'metadata' => [
                ...($payment->metadata ?? []),
                'authorize_uri' => $charge->authorizeUri,
                'card' => $charge->raw['card'] ?? null,
            ],
        ], fn ($value) => $value !== null));

        $this->apply($payment->refresh(), $charge, 'card.charge');

        return $payment->refresh();
    }

    public function refresh(Payment $payment): Payment
    {
        if ($payment->status !== PaymentStatus::Pending->value || ! $payment->isGateway()) {
            return $payment;
        }

        $gateway = $payment->payment_method === PaymentMethod::ThaiQr->value
            ? $this->gatewayFor($payment, 'qr')
            : $this->gatewayFor($payment, 'card');

        $charge = $gateway->inquire($payment);

        if (! $charge->isPaid() && $payment->payment_method === PaymentMethod::ThaiQr->value && $payment->isExpired()) {
            $charge = new GatewayCharge(GatewayCharge::FAILED, failureCode: 'qr_expired', failureMessage: 'QR หมดอายุ', raw: $charge->raw);
        }

        $this->apply($payment, $charge, 'inquiry');

        return $payment->refresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleKgpNotification(array $payload): bool
    {
        $gateway = app(KgpQrGateway::class);

        if (! $gateway->verifyNotification($payload)) {
            Log::warning('KGP notify: checksum ไม่ตรง', ['reference_order' => $payload['reference_order'] ?? null]);

            return false;
        }

        $sourceId = data_get($payload, 'source.0.source_id') ?? data_get($payload, 'source.source_id');
        $payment = Payment::query()
            ->where('provider', 'kgp')
            ->where(function ($query) use ($payload, $sourceId) {
                $query->where('reference', (string) ($payload['reference_order'] ?? ''));
                if (filled($sourceId)) {
                    $query->orWhere('transaction_id', trim((string) $sourceId));
                }
            })
            ->first();

        if (! $payment) {
            Log::warning('KGP notify: ไม่พบรายการชำระ', ['reference_order' => $payload['reference_order'] ?? null]);

            return true;
        }

        $this->apply($payment, $gateway->chargeFromPayload($payload), 'notify');

        return true;
    }

    /**
     * KGP Refund Notification (สเปกหัวข้อ 8) ไม่มี checksum จึงยืนยันกับ Refund Inquiry ก่อนเสมอ
     *
     * @param  array<string, mixed>  $payload
     * @return 'ok'|'invalid'|'unverified'
     */
    public function handleKgpRefundNotification(array $payload): string
    {
        $refundId = trim((string) ($payload['refund_id'] ?? ''));
        $chargeId = trim((string) ($payload['charge_id'] ?? ''));

        if ($refundId === '' || $chargeId === '') {
            return 'invalid';
        }

        $payment = Payment::query()
            ->where('provider', 'kgp')
            ->where('metadata->charge_id', $chargeId)
            ->first();

        if (! $payment) {
            Log::warning('KGP refund notify: ไม่พบรายการชำระ', ['charge_id' => $chargeId, 'refund_id' => $refundId]);

            return 'ok';
        }

        $confirmed = app(KgpQrGateway::class)->inquireRefund($refundId);

        if ($confirmed === null || (string) ($confirmed['charge_id'] ?? '') !== $chargeId) {
            $this->log($payment, 'refund.unverified', null, $refundId, $this->refundFields($payload));
            Log::warning('KGP refund notify: ยืนยันกับ Refund Inquiry ไม่ได้', ['refund_id' => $refundId]);

            return 'unverified';
        }

        $fields = $this->refundFields($confirmed);
        $this->log($payment, 'refund.notify', $fields['status'], $refundId, $fields);

        DB::transaction(function () use ($payment, $refundId, $fields) {
            $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $refunds = $payment->metadata['refunds'] ?? [];
            $refunds[$refundId] = [...$fields, 'updated_at' => now()->toIso8601String()];

            $payload = ['metadata' => [...($payment->metadata ?? []), 'refunds' => $refunds]];

            if ($fields['status'] === 'success' && $fields['refund_amount_type'] === 'F' && $payment->status === PaymentStatus::Paid->value) {
                $payload['status'] = PaymentStatus::Refunded->value;

                Order::query()
                    ->whereKey($payment->order_id)
                    ->where('payment_status', PaymentStatus::Paid->value)
                    ->update(['payment_status' => PaymentStatus::Refunded->value]);
            }

            $payment->update($payload);
        });

        return 'ok';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{refund_id: ?string, refund_ref_id: ?string, charge_id: ?string, status: ?string, refund_amount_type: ?string, refund_day_type: ?string, failure_code: ?string, failure_message: ?string}
     */
    private function refundFields(array $data): array
    {
        $value = fn (string $key) => isset($data[$key]) && $data[$key] !== '' ? trim((string) $data[$key]) : null;

        return [
            'refund_id' => $value('refund_id'),
            'refund_ref_id' => $value('refund_ref_id'),
            'charge_id' => $value('charge_id'),
            'status' => $value('status') !== null ? strtolower((string) $value('status')) : null,
            'refund_amount_type' => $value('refund_amount_type') !== null ? strtoupper((string) $value('refund_amount_type')) : null,
            'refund_day_type' => $value('refund_day_type'),
            'failure_code' => $value('failure_code'),
            'failure_message' => $value('failure_message'),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleOmiseEvent(array $payload): void
    {
        $chargeId = data_get($payload, 'data.object') === 'charge' ? data_get($payload, 'data.id') : null;

        if (blank($chargeId)) {
            return;
        }

        $payment = Payment::query()
            ->where('provider', 'omise')
            ->where('transaction_id', (string) $chargeId)
            ->first();

        if ($payment) {
            $this->log($payment, 'webhook.'.(string) ($payload['key'] ?? 'event'), null, (string) $chargeId, [
                'event_id' => $payload['id'] ?? null,
            ]);
            $this->refresh($payment);
        }
    }

    public function simulate(Payment $payment, string $state): Payment
    {
        abort_unless($payment->provider === 'fake', 404);

        $payment->update([
            'metadata' => [
                ...($payment->metadata ?? []),
                'fake_state' => $state,
                'charge_id' => $payment->metadata['charge_id'] ?? 'chrg_fake_'.Str::lower(Str::random(20)),
            ],
        ]);

        return $this->refresh($payment->refresh());
    }

    public function returnUri(Payment $payment, string $next): string
    {
        return URL::temporarySignedRoute('payment.return', now()->addHours(2), [
            'payment' => $payment->id,
            'next' => $next,
        ]);
    }

    public function appReturnUrl(Order $order): string
    {
        $template = (string) (config('payment.return_url')
            ?: rtrim((string) (config('services.storefront.app_url')
                ?: trim(explode(',', (string) config('services.storefront.frontend_url'))[0])), '/').'/orders/{order_id}');

        $guestToken = (string) ($order->guest_token ?? '');

        $url = strtr($template, [
            '{order_id}' => (string) $order->id,
            '{order_no}' => rawurlencode((string) $order->order_no),
            '{guest_token}' => rawurlencode($guestToken),
        ]);

        if ($guestToken !== '' && ! str_contains($template, '{guest_token}')) {
            $url .= (str_contains($url, '?') ? '&' : '?').'guest_token='.rawurlencode($guestToken);
        }

        return $url;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(?Payment $payment, Order $order): array
    {
        $order->refresh();
        $method = PaymentMethod::tryFrom((string) ($payment?->payment_method ?? ''));

        return [
            'order_id' => $order->id,
            'order_no' => $order->order_no,
            'order_payment_status' => $order->payment_status,
            'paid' => $order->payment_status === PaymentStatus::Paid->value,
            'payment' => $payment ? [
                'id' => $payment->id,
                'method' => $payment->payment_method,
                'method_label' => $method?->label(),
                'provider' => $payment->provider,
                'reference' => $payment->reference,
                'status' => $payment->status,
                'status_label' => PaymentStatus::tryFrom((string) $payment->status)?->label(),
                'amount' => (float) $payment->amount,
                'order_amount' => round((float) $payment->amount - (float) $payment->fee_amount, 2),
                'fee_amount' => (float) $payment->fee_amount,
                'fee_label' => (float) $payment->fee_amount > 0 ? ($payment->metadata['fee']['label'] ?? 'ค่าธรรมเนียม') : null,
                'currency' => 'THB',
                'expires_at' => $payment->expires_at?->toIso8601String(),
                'paid_at' => $payment->paid_at?->toIso8601String(),
                'failure_code' => $payment->failure_code,
                'failure_message' => $payment->failure_message,
                'qr_image' => $method === PaymentMethod::ThaiQr && $payment->status === PaymentStatus::Pending->value
                    ? ($payment->metadata['qr_image'] ?? null)
                    : null,
                'merchant_name' => $payment->metadata['account_name'] ?? config('payment.merchant_name'),
                'authorize_uri' => $payment->status === PaymentStatus::Pending->value
                    ? ($payment->metadata['authorize_uri'] ?? null)
                    : null,
            ] : null,
        ];
    }

    private function gatewayFor(Payment $payment, string $kind): ThaiQrGateway|CardGateway
    {
        return match ($payment->provider) {
            'fake' => app(FakeGateway::class),
            'kgp' => app(KgpQrGateway::class),
            'omise' => app(OmiseCardGateway::class),
            default => $kind === 'qr' ? $this->qrGateway() : $this->cardGateway(),
        };
    }

    private function newAttempt(Order $order, PaymentMethod $method, string $provider): Payment
    {
        return DB::transaction(function () use ($order, $method, $provider) {
            Order::query()->whereKey($order->id)->lockForUpdate()->first();

            $base = round((float) $order->total_amount, 2);
            $fee = PaymentFees::calculate($method, $base);

            $payment = Payment::query()->create([
                'order_id' => $order->id,
                'payment_method' => $method->value,
                'provider' => $provider,
                'amount' => round($base + $fee, 2),
                'fee_amount' => $fee,
                'status' => PaymentStatus::Pending->value,
                'metadata' => array_filter([
                    'channel' => 'gateway',
                    'created_by' => $order->user_id,
                    'fee' => $fee > 0 ? PaymentFees::snapshot($method) : null,
                ], fn ($value) => $value !== null),
            ]);

            $payment->update(['reference' => 'CCT'.str_pad((string) $payment->id, 10, '0', STR_PAD_LEFT)]);

            return $payment->refresh();
        });
    }

    private function supersedePending(Order $order): void
    {
        $pending = $order->payments()
            ->where('status', PaymentStatus::Pending->value)
            ->whereIn('provider', ['kgp', 'omise', 'fake'])
            ->get();

        foreach ($pending as $payment) {
            if ($payment->payment_method === PaymentMethod::ThaiQr->value) {
                $this->gatewayFor($payment, 'qr')->cancel($payment);
            }

            $this->markFailed($payment, 'superseded', 'เริ่มการชำระใหม่', 'superseded');
        }
    }

    private function apply(Payment $payment, GatewayCharge $charge, string $event): void
    {
        $this->log($payment, $event, $charge->status, $charge->chargeId, $charge->raw);

        if ($charge->isPaid()) {
            if ($charge->amount !== null && abs($charge->amount - (float) $payment->amount) > 0.009) {
                Log::error('ยอดชำระไม่ตรงกับออเดอร์', [
                    'payment_id' => $payment->id,
                    'expected' => (float) $payment->amount,
                    'received' => $charge->amount,
                ]);
                $this->markFailed($payment, 'amount_mismatch', 'ยอดที่ชำระไม่ตรงกับออเดอร์ ติดต่อแอดมิน', 'amount_mismatch');

                return;
            }

            $this->markPaid($payment, $charge);

            return;
        }

        if ($charge->isFailed()) {
            $this->markFailed($payment, $charge->failureCode, $charge->failureMessage ?? 'ชำระเงินไม่สำเร็จ', null);
        }
    }

    private function markPaid(Payment $payment, GatewayCharge $charge): void
    {
        $payment->refresh();

        if ($payment->status === PaymentStatus::Paid->value) {
            return;
        }

        $payment->update([
            'failure_code' => null,
            'failure_message' => null,
            'metadata' => [
                ...($payment->metadata ?? []),
                'charge_id' => $charge->chargeId ?? ($payment->metadata['charge_id'] ?? null),
            ],
        ]);

        $this->purchases->review($payment, PaymentStatus::Paid);

        Payment::query()
            ->where('order_id', $payment->order_id)
            ->whereKeyNot($payment->id)
            ->where('status', PaymentStatus::Pending->value)
            ->whereDoesntHave('slips')
            ->update([
                'status' => PaymentStatus::Failed->value,
                'failure_code' => 'superseded',
                'failure_message' => 'ชำระด้วยรายการอื่นแล้ว',
            ]);

        PaymentLink::query()
            ->where('order_id', $payment->order_id)
            ->where('status', 'active')
            ->update(['status' => 'used', 'used_at' => now()]);
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    private function markFailed(Payment $payment, ?string $code, ?string $message, ?string $event, array $raw = []): void
    {
        $payment->refresh();

        if ($payment->status !== PaymentStatus::Pending->value) {
            return;
        }

        $payment->update([
            'status' => PaymentStatus::Failed->value,
            'failure_code' => $code !== null ? Str::limit($code, 50, '') : null,
            'failure_message' => $message !== null ? Str::limit($message, 250, '') : null,
        ]);

        if ($event !== null) {
            $this->log($payment, $event, 'failed', $payment->transaction_id, $raw);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function log(Payment $payment, string $event, ?string $status, ?string $providerId, array $payload): void
    {
        PaymentTransaction::query()->create([
            'payment_id' => $payment->id,
            'provider' => $payment->provider,
            'provider_transaction_id' => $providerId,
            'event_type' => $event,
            'status' => $status,
            'payload' => $payload,
            'processed_at' => now(),
        ]);
    }
}
