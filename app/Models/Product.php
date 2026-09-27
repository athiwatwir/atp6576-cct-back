<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Support\MediaStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use LogsActivity;
    use SoftDeletes;

    public function getAuditLabel(): string
    {
        return 'หนังสือ '.($this->code ?: '#'.$this->id).' '.$this->name;
    }

    protected $fillable = [
        'code',
        'name',
        'slug',
        'type',
        'description',
        'thumbnail',
        'price',
        'sale_price',
        'stock',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'stock' => 'integer',
        ];
    }

    public function scopeBooks(Builder $query): Builder
    {
        return $query->where('type', 'book');
    }

    public function scopeForSale(Builder $query): Builder
    {
        return $query->books()->where('status', 'active');
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return MediaStorage::url($this->thumbnail);
    }

    public function getEffectivePriceAttribute(): float
    {
        if ($this->sale_price !== null && (float) $this->sale_price > 0) {
            return (float) $this->sale_price;
        }

        return (float) $this->price;
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
