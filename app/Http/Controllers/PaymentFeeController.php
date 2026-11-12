<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Models\PaymentFee;
use App\Support\Payments\OmiseCardGateway;
use App\Support\Payments\OnlinePayment;
use App\Support\Payments\PaymentFees;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * หน้า วิธี/ค่าธรรมเนียมการชำระเงิน: เปิดปิดแต่ละวิธีให้ลูกค้าใช้ และตั้งค่าธรรมเนียม
 */
class PaymentFeeController extends Controller
{
    public function edit(OnlinePayment $payments): View
    {
        $fees = PaymentFees::all();
        $fees->each(fn (PaymentFee $fee) => $fee->loadMissing('updater:id,name'));
        $capabilities = app(OmiseCardGateway::class)->capabilities();

        $status = collect(PaymentMethod::customerMethods())->mapWithKeys(function (PaymentMethod $method) use ($payments, $capabilities) {
            $note = match (true) {
                $method === PaymentMethod::ThaiQr => $payments->isFake(PaymentMethod::ThaiQr) ? 'KGP โหมดทดสอบ (QR จำลอง)' : 'KGP',
                $method === PaymentMethod::CreditCard => $payments->isFake(PaymentMethod::CreditCard) ? 'Omise โหมดทดสอบ (บัตรจำลอง)' : 'Omise',
                $method === PaymentMethod::BankTransfer => 'ลูกค้าโอนเงินแล้วแนบสลิป แอดมินตรวจในหน้าการเงิน',
                default => 'Omise',
            };

            $warning = null;
            if ($method->isWallet()) {
                $sources = array_unique([$method->omiseSourceType('desktop'), $method->omiseSourceType('mobile')]);
                $warning = match (true) {
                    ! $payments->isAvailable($method) => 'ต้องตั้ง PAYMENT_CARD_DRIVER=live และคีย์ Omise ก่อน ระบบจึงจะแสดงให้ลูกค้า',
                    $capabilities === null => 'ตรวจกับ Omise ไม่ได้ว่าบัญชีเปิดช่องทางนี้หรือไม่',
                    array_diff($sources, $capabilities) !== [] => 'บัญชี Omise ยังไม่เปิดช่องทางนี้ ('.implode(', ', array_diff($sources, $capabilities)).') ติดต่อ Omise ก่อนเปิดใช้',
                    default => null,
                };
            }

            return [$method->value => [
                'provider_note' => $note,
                'warning' => $warning,
                'test_key' => $method->provider() === 'omise' && str_starts_with((string) config('payment.omise.secret_key'), 'skey_test_'),
            ]];
        });

        return view('pages.settings.payment-fees', [
            'title' => 'วิธี/ค่าธรรมเนียมการชำระเงิน',
            'fees' => $fees,
            'status' => $status,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $methods = array_map(fn (PaymentMethod $method) => $method->value, PaymentFees::configurableMethods());

        $rows = collect((array) $request->input('fees', []))
            ->only($methods)
            ->map(fn ($row) => [
                'is_active' => filter_var($row['is_active'] ?? false, FILTER_VALIDATE_BOOL),
                'is_enabled' => filter_var($row['is_enabled'] ?? false, FILTER_VALIDATE_BOOL),
                'percent' => filled($row['percent'] ?? null) ? $row['percent'] : 0,
                'fixed_amount' => filled($row['fixed_amount'] ?? null) ? $row['fixed_amount'] : 0,
                'min_amount' => filled($row['min_amount'] ?? null) ? $row['min_amount'] : null,
                'max_amount' => filled($row['max_amount'] ?? null) ? $row['max_amount'] : null,
                'label' => filled($row['label'] ?? null) ? trim((string) $row['label']) : null,
            ])
            ->all();

        $data = validator(['fees' => $rows], [
            'fees' => ['required', 'array'],
            'fees.*.is_active' => ['boolean'],
            'fees.*.is_enabled' => ['boolean'],
            'fees.*.percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'fees.*.fixed_amount' => ['required', 'numeric', 'min:0', 'max:99999'],
            'fees.*.min_amount' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'fees.*.max_amount' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'fees.*.label' => ['nullable', 'string', 'max:100'],
        ], [], [
            'fees.*.percent' => 'เปอร์เซ็นต์',
            'fees.*.fixed_amount' => 'ค่าคงที่',
            'fees.*.min_amount' => 'ขั้นต่ำ',
            'fees.*.max_amount' => 'สูงสุด',
            'fees.*.label' => 'ข้อความที่ลูกค้าเห็น',
        ])->validate()['fees'];

        foreach ($data as $method => $row) {
            if ($row['min_amount'] !== null && $row['max_amount'] !== null && (float) $row['min_amount'] > (float) $row['max_amount']) {
                throw ValidationException::withMessages([
                    "fees.{$method}.max_amount" => 'ค่าธรรมเนียมสูงสุดต้องไม่น้อยกว่าขั้นต่ำ ('.PaymentMethod::from($method)->label().')',
                ]);
            }
        }

        DB::transaction(function () use ($data, $request) {
            foreach ($data as $method => $row) {
                PaymentFee::query()->updateOrCreate(
                    ['payment_method' => $method],
                    [...$row, 'updated_by' => $request->user()?->id],
                );
            }
        });

        return redirect()
            ->route('payment-fees.edit')
            ->with('success', 'บันทึกวิธีชำระและค่าธรรมเนียมเรียบร้อยแล้ว มีผลกับการชำระที่เริ่มหลังจากนี้');
    }
}
