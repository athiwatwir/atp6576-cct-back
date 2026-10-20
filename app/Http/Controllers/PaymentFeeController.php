<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Models\PaymentFee;
use App\Support\Payments\PaymentFees;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PaymentFeeController extends Controller
{
    public function edit(): View
    {
        $fees = PaymentFees::all();
        $fees->each(fn (PaymentFee $fee) => $fee->loadMissing('updater:id,name'));

        return view('pages.settings.payment-fees', [
            'title' => 'ค่าธรรมเนียมการชำระเงิน',
            'fees' => $fees,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $methods = array_map(fn (PaymentMethod $method) => $method->value, PaymentFees::configurableMethods());

        $rows = collect((array) $request->input('fees', []))
            ->only($methods)
            ->map(fn ($row) => [
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
            ->with('success', 'บันทึกค่าธรรมเนียมเรียบร้อยแล้ว มีผลกับการชำระที่เริ่มหลังจากนี้');
    }
}
