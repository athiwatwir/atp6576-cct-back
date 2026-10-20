<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    protected $fillable = [
        'receipt_no',
        'order_id',
        'payment_id',
        'user_id',
        'issued_at',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'payment_method',
        'payment_reference',
        'items',
        'subtotal',
        'discount_amount',
        'shipping_amount',
        'fee_amount',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'items' => 'array',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'fee_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function filename(): string
    {
        return 'receipt-'.$this->receipt_no.'.pdf';
    }
}
