<?php

namespace App\Http\Requests\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
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
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(array_column(OrderStatus::cases(), 'value'))],
            'payment_status' => ['required', Rule::in(array_column(PaymentStatus::cases(), 'value'))],
            'payment_method' => ['required', Rule::in(array_column(PaymentMethod::cases(), 'value'))],
            'shipping_status' => ['required', Rule::in(array_column(ShippingStatus::cases(), 'value'))],
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
            'shipping_carrier' => ['nullable', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => 'สถานะออเดอร์',
            'payment_status' => 'สถานะชำระเงิน',
            'payment_method' => 'วิธีชำระเงิน',
            'shipping_status' => 'สถานะจัดส่ง',
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
            'shipping_carrier' => 'บริษัทขนส่ง',
            'tracking_number' => 'หมายเลขพัสดุ',
        ];
    }
}
