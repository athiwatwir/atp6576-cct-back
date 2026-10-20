<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentFee extends Model
{
    use LogsActivity;

    protected $fillable = [
        'payment_method',
        'is_enabled',
        'percent',
        'fixed_amount',
        'min_amount',
        'max_amount',
        'label',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'percent' => 'decimal:2',
            'fixed_amount' => 'decimal:2',
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
        ];
    }

    public function getAuditLabel(): string
    {
        return 'ค่าธรรมเนียม '.(\App\Enums\PaymentMethod::tryFrom($this->payment_method)?->label() ?? $this->payment_method);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
