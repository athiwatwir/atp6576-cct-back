<?php

namespace App\Http\Requests\Api;

class GuestOrderRequest extends StorefrontOrderRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $email = strtolower(trim((string) $this->input('email', '')));

        $this->merge([
            'email' => $email === '' ? null : $email,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'email' => [$this->isQuote() ? 'nullable' : 'required', 'string', 'email:rfc', 'max:255'],
            'name' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            ...parent::attributes(),
            'email' => 'อีเมล',
            'name' => 'ชื่อ',
            'phone' => 'เบอร์โทร',
        ];
    }
}
