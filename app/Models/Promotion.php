<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promotion extends Model
{
    use LogsActivity;
    use SoftDeletes;

    public function getAuditLabel(): string
    {
        return 'โปรโมชัน '.$this->name;
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
        'discount_type',
        'discount_value',
        'max_discount_amount',
        'min_purchase_amount',
        'usage_limit',
        'usage_count',
        'start_at',
        'end_at',
        'status',
        'rules',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'max_discount_amount' => 'decimal:2',
            'min_purchase_amount' => 'decimal:2',
            'usage_limit' => 'integer',
            'usage_count' => 'integer',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'rules' => 'array',
        ];
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'promotion_courses');
    }

    public function curriculums(): BelongsToMany
    {
        return $this->belongsToMany(Curriculum::class, 'promotion_curriculums');
    }

    public function assessments(): BelongsToMany
    {
        return $this->belongsToMany(Assessment::class, 'promotion_assessments');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'promotion_products');
    }

    public function requiredCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'promotion_required_courses');
    }

    public function requiredProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'promotion_required_products');
    }

    public function requiredVideos(): BelongsToMany
    {
        return $this->belongsToMany(Video::class, 'promotion_required_videos');
    }

    public function giftCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'promotion_gift_courses');
    }

    public function giftAssessments(): BelongsToMany
    {
        return $this->belongsToMany(Assessment::class, 'promotion_gift_assessments');
    }

    public function giftProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'promotion_gift_products');
    }

    public function studentGroup(): string
    {
        $group = $this->rules['student_group'] ?? 'all';

        return in_array($group, ['all', 'new', 'returning'], true) ? $group : 'all';
    }

    public function studentGroupLabel(): string
    {
        return match ($this->studentGroup()) {
            'new' => 'นักเรียนใหม่',
            'returning' => 'นักเรียนเก่า',
            default => 'ทุกคน',
        };
    }

    public function requiredPurchaseLabel(): string
    {
        $items = $this->requiredCourses->pluck('name')
            ->map(fn (string $name) => 'คอร์ส '.$name)
            ->merge($this->requiredProducts->pluck('name')->map(fn (string $name) => 'หนังสือ '.$name));

        return $items->isEmpty() ? 'ไม่ต้องเคยซื้อ' : 'ต้องเคยซื้อ '.$items->join(', ');
    }

    public function giftLabel(): string
    {
        $triggers = $this->giftCourses->pluck('name');
        $gifts = $this->giftAssessments->pluck('title')
            ->map(fn (string $title) => 'ข้อสอบ '.$title)
            ->merge($this->giftProducts->pluck('name')->map(fn (string $name) => 'หนังสือ '.$name));

        if ($triggers->isEmpty() && $gifts->isEmpty()) {
            return 'ไม่มีของแถม';
        }

        if ($triggers->isEmpty()) {
            return 'แถม '.$gifts->join(', ');
        }

        if ($gifts->isEmpty()) {
            return 'ซื้อ '.$triggers->join(', ');
        }

        return 'ซื้อ '.$triggers->join(', ').' แถม '.$gifts->join(', ');
    }

    public function requiredVideoLabel(): string
    {
        $titles = $this->requiredVideos->pluck('title');

        return $titles->isEmpty() ? 'ไม่ต้องดูวิดีโอก่อน' : 'ต้องดู '.$titles->join(', ').' ก่อน';
    }

    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'content_promotions');
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
