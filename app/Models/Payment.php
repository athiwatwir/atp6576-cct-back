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
        'reference',
        'amount',
        'fee_amount',
        'status',
        'paid_at',
        'expires_at',
        'failure_code',
        'failure_message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'fee_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
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

    public function isGateway(): bool
    {
        return in_array($this->provider, ['kgp', 'omise', 'fake'], true);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function sourceLabel(): string
    {
        return match ($this->metadata['channel'] ?? null) {
            'student-admin' => 'สมัครเรียน',
            'storefront' => 'เว็บไซต์',
            'gateway' => 'ชำระออนไลน์',
            default => 'ออเดอร์',
        };
    }
}
