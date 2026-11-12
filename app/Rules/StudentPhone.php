<?php

namespace App\Rules;

use App\Models\User;
use App\Support\Phone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * เบอร์โทรใช้เป็นชื่อเข้าสู่ระบบของนักเรียน จึงต้องเป็นรูปแบบเบอร์โทรและไม่ซ้ำกับนักเรียนคนอื่น
 */
class StudentPhone implements ValidationRule
{
    public function __construct(private readonly ?int $ignoreUserId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        $phone = Phone::normalize($value);

        if (! Phone::looksLikePhone($value) || $phone === null || strlen($phone) < 9 || strlen($phone) > 15) {
            $fail('รูปแบบเบอร์โทรไม่ถูกต้อง');

            return;
        }

        $taken = User::query()
            ->students()
            ->where('phone', $phone)
            ->when($this->ignoreUserId, fn ($query) => $query->whereKeyNot($this->ignoreUserId))
            ->exists();

        if ($taken) {
            $fail('เบอร์โทรนี้ถูกใช้กับบัญชีอื่นแล้ว');
        }
    }
}
