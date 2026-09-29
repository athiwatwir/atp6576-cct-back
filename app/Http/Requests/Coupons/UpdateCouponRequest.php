<?php

namespace App\Http\Requests\Coupons;

use Illuminate\Validation\Rule;

class UpdateCouponRequest extends StoreCouponRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $couponId = $this->route('coupon')?->id;
        $rules['code'] = ['required', 'string', 'max:100', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('coupons', 'code')->ignore($couponId)];

        return $rules;
    }
}
