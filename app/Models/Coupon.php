<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use LogsActivity;
    use SoftDeletes;

    public function getAuditLabel(): string
    {
        return 'คูปอง '.$this->code;
    }

    protected $fillable = [
        'code',
        'description',
        'discount_type',
        'discount_value',
        'max_discount_amount',
        'min_purchase_amount',
        'usage_limit',
        'usage_count',
        'per_user_limit',
        'start_at',
        'end_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'max_discount_amount' => 'decimal:2',
            'min_purchase_amount' => 'decimal:2',
            'usage_limit' => 'integer',
            'usage_count' => 'integer',
            'per_user_limit' => 'integer',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'coupon_courses');
    }

    public function curriculums(): BelongsToMany
    {
        return $this->belongsToMany(Curriculum::class, 'coupon_curriculums');
    }

    public function assessments(): BelongsToMany
    {
        return $this->belongsToMany(Assessment::class, 'coupon_assessments');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'coupon_products');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'content_coupons');
    }

    public function discountLabel(): string
    {
        if ($this->discount_type === DiscountType::Percentage->value) {
            $label = rtrim(rtrim(number_format((float) $this->discount_value, 2, '.', ''), '0'), '.').'%';

            if ($this->max_discount_amount !== null && (float) $this->max_discount_amount > 0) {
                $label .= ' สูงสุด ฿'.number_format((float) $this->max_discount_amount, 2);
            }

            return $label;
        }

        return '฿'.number_format((float) $this->discount_value, 2);
    }

    public function isRestricted(): bool
    {
        if ($this->relationLoaded('courses') || $this->relationLoaded('curriculums') || $this->relationLoaded('assessments') || $this->relationLoaded('products')) {
            return $this->courses->isNotEmpty()
                || $this->curriculums->isNotEmpty()
                || $this->assessments->isNotEmpty()
                || $this->products->isNotEmpty();
        }

        return $this->courses()->exists()
            || $this->curriculums()->exists()
            || $this->assessments()->exists()
            || $this->products()->exists();
    }
}
