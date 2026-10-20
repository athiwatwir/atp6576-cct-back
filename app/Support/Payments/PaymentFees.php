<?php

namespace App\Support\Payments;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\PaymentFee;
use Illuminate\Support\Collection;

/**
 * ค่าธรรมเนียมที่เก็บเพิ่มจากลูกค้าตามวิธีชำระ
 * ค่าธรรมเนียมบวกเข้ายอดของรายการชำระ (payments.amount) ไม่เปลี่ยนยอดออเดอร์
 */
class PaymentFees
{
    /**
     * @return list<PaymentMethod>
     */
    public static function configurableMethods(): array
    {
        return [PaymentMethod::ThaiQr, PaymentMethod::CreditCard, PaymentMethod::BankTransfer];
    }

    /**
     * @return Collection<string, PaymentFee>
     */
    public static function all(): Collection
    {
        $saved = PaymentFee::query()->get()->keyBy('payment_method');

        return collect(self::configurableMethods())
            ->mapWithKeys(fn (PaymentMethod $method) => [
                $method->value => $saved->get($method->value) ?? new PaymentFee([
                    'payment_method' => $method->value,
                    'is_enabled' => false,
                    'percent' => 0,
                    'fixed_amount' => 0,
                ]),
            ]);
    }

    public static function for(PaymentMethod $method): ?PaymentFee
    {
        if (! in_array($method, self::configurableMethods(), true)) {
            return null;
        }

        return PaymentFee::query()->where('payment_method', $method->value)->first();
    }

    public static function calculate(PaymentMethod $method, float $baseAmount, ?PaymentFee $fee = null): float
    {
        $fee ??= self::for($method);

        if (! $fee || ! $fee->is_enabled || $baseAmount <= 0) {
            return 0.0;
        }

        $amount = $baseAmount * ((float) $fee->percent / 100) + (float) $fee->fixed_amount;

        if ($fee->min_amount !== null) {
            $amount = max($amount, (float) $fee->min_amount);
        }

        if ($fee->max_amount !== null) {
            $amount = min($amount, (float) $fee->max_amount);
        }

        return round(max(0, $amount), 2);
    }

    /**
     * ยอดที่ลูกค้าต้องจ่ายต่อวิธีชำระสำหรับออเดอร์นี้
     *
     * @param  list<string>  $methods
     * @return array<string, array{method: string, label: string, fee_label: ?string, order_amount: float, fee_amount: float, total: float}>
     */
    public static function quote(Order $order, array $methods): array
    {
        $fees = self::all();
        $base = round((float) $order->total_amount, 2);
        $result = [];

        foreach ($methods as $value) {
            $method = PaymentMethod::tryFrom($value);

            if (! $method) {
                continue;
            }

            $fee = $fees->get($value);
            $feeAmount = self::calculate($method, $base, $fee);

            $result[$value] = [
                'method' => $value,
                'label' => $method->label(),
                'fee_label' => $feeAmount > 0 ? self::customerLabel($method, $fee) : null,
                'order_amount' => $base,
                'fee_amount' => $feeAmount,
                'total' => round($base + $feeAmount, 2),
            ];
        }

        return $result;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function rules(): array
    {
        return self::all()
            ->map(fn (PaymentFee $fee, string $method) => [
                'method' => $method,
                'enabled' => (bool) $fee->is_enabled,
                'label' => $fee->is_enabled ? self::customerLabel(PaymentMethod::from($method), $fee) : null,
                'percent' => (float) $fee->percent,
                'fixed_amount' => (float) $fee->fixed_amount,
                'min_amount' => $fee->min_amount !== null ? (float) $fee->min_amount : null,
                'max_amount' => $fee->max_amount !== null ? (float) $fee->max_amount : null,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function snapshot(PaymentMethod $method): array
    {
        $fee = self::for($method);

        if (! $fee || ! $fee->is_enabled) {
            return [];
        }

        return [
            'percent' => (float) $fee->percent,
            'fixed_amount' => (float) $fee->fixed_amount,
            'min_amount' => $fee->min_amount !== null ? (float) $fee->min_amount : null,
            'max_amount' => $fee->max_amount !== null ? (float) $fee->max_amount : null,
            'label' => self::customerLabel($method, $fee),
        ];
    }

    public static function customerLabel(PaymentMethod $method, ?PaymentFee $fee): string
    {
        if ($fee && filled($fee->label)) {
            return (string) $fee->label;
        }

        $parts = [];

        if ($fee && (float) $fee->percent > 0) {
            $parts[] = rtrim(rtrim(number_format((float) $fee->percent, 2), '0'), '.').'%';
        }

        if ($fee && (float) $fee->fixed_amount > 0) {
            $parts[] = number_format((float) $fee->fixed_amount, 2).' บาท';
        }

        $name = $method->label();
        $joiner = preg_match('/^[A-Za-z]/', $name) ? ' ' : '';

        return 'ค่าธรรมเนียม'.$joiner.$name.($parts !== [] ? ' ('.implode(' + ', $parts).')' : '');
    }
}
