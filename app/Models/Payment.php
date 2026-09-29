<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'payment_method',
        'provider',
        'transaction_id',
        'amount',
        'status',
        'paid_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function slips(): HasMany
    {
        return $this->hasMany(PaymentSlip::class);
    }

    public function sourceLabel(): string
    {
        return match ($this->metadata['channel'] ?? null) {
            'student-admin' => 'สมัครเรียน',
            'storefront' => 'เว็บไซต์',
            default => 'ออเดอร์',
        };
    }
}
