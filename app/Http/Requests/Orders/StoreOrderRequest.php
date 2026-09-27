<?php

namespace App\Http\Requests\Orders;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('shipping_amount') === '') {
            $this->merge(['shipping_amount' => 0]);
        }

        if ($this->input('discount_amount') === '') {
            $this->merge(['discount_amount' => 0]);
        }

        $mode = $this->input('customer_mode', 'existing');

        if ($mode === 'manual') {
            $this->merge(['user_id' => null]);
        }

        if ($mode === 'existing') {
            $this->merge([
                'customer_name' => null,
                'customer_email' => null,
                'customer_phone' => null,
            ]);
        }

        if ($this->input('customer_email') === '') {
            $this->merge(['customer_email' => null]);
        }

        $items = collect($this->input('items', []))
            ->filter(fn ($item) => filled($item['product_id'] ?? null))
            ->values()
            ->all();

        $this->merge([
            'customer_mode' => $mode,
            'items' => $items,
            'copy_customer_to_shipping' => $this->boolean('copy_customer_to_shipping'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $mode = $this->input('customer_mode', 'existing');

        return [
            'customer_mode' => ['required', Rule::in(['existing', 'manual'])],
            'user_id' => [
                Rule::requiredIf($mode === 'existing'),
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'customer_name' => [
                Rule::requiredIf($mode === 'manual'),
                'nullable',
                'string',
                'max:150',
            ],
            'customer_email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'customer_phone' => [
                Rule::requiredIf($mode === 'manual'),
                'nullable',
                'string',
                'max:30',
            ],
            'copy_customer_to_shipping' => ['nullable', 'boolean'],
            'payment_method' => ['required', Rule::in(array_column(PaymentMethod::cases(), 'value'))],
            'shipping_amount' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'discount_amount' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'shipping_name' => ['required', 'string', 'max:150'],
            'shipping_phone' => ['required', 'string', 'max:30'],
            'shipping_address_line1' => ['required', 'string', 'max:255'],
            'shipping_subdistrict' => ['nullable', 'string', 'max:100'],
            'shipping_district' => ['required', 'string', 'max:100'],
            'shipping_province' => ['required', 'string', 'max:100'],
            'shipping_postal_code' => ['required', 'string', 'max:20'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'customer_mode' => 'ประเภทลูกค้า',
            'user_id' => 'ลูกค้า',
            'customer_name' => 'ชื่อลูกค้า',
            'customer_email' => 'อีเมลลูกค้า',
            'customer_phone' => 'เบอร์โทรลูกค้า',
            'payment_method' => 'วิธีชำระเงิน',
            'shipping_amount' => 'ค่าจัดส่ง',
            'discount_amount' => 'ส่วนลด',
            'notes' => 'หมายเหตุ',
            'shipping_name' => 'ชื่อผู้รับ',
            'shipping_phone' => 'เบอร์โทร',
            'shipping_address_line1' => 'ที่อยู่',
            'shipping_subdistrict' => 'ตำบล/แขวง',
            'shipping_district' => 'อำเภอ/เขต',
            'shipping_province' => 'จังหวัด',
            'shipping_postal_code' => 'รหัสไปรษณีย์',
            'items' => 'รายการสินค้า',
            'items.*.product_id' => 'หนังสือ',
            'items.*.quantity' => 'จำนวน',
        ];
    }
}
