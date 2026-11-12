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
        'student_group',
        'purchase_type',
        'required_video_seconds',
        'start_at',
        'end_at',
        'status',
    ];

    public const STUDENT_GROUPS = [
        'all' => 'ทุกคน',
        'new' => 'นักเรียนใหม่',
        'returning' => 'นักเรียนเก่า',
    ];

    /** ต่ออายุ = เคยมีสิทธิ์เรียนวิชา/หลักสูตร/ข้อสอบนั้นมาก่อน (ยังใช้ได้หรือหมดอายุแล้วก็ได้) */
    public const PURCHASE_TYPES = [
        'any' => 'ทุกการซื้อ',
        'new' => 'ซื้อใหม่เท่านั้น',
        'renewal' => 'ต่ออายุเท่านั้น',
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
            'required_video_seconds' => 'integer',
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

    public function requiredCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'coupon_required_courses');
    }

    public function requiredProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'coupon_required_products');
    }

    public function requiredVideos(): BelongsToMany
    {
        return $this->belongsToMany(Video::class, 'coupon_required_videos');
    }

    /** ต้องซื้อวิชาเหล่านี้ในออเดอร์จึงได้ของแถม ไม่เลือก = ทุกออเดอร์ที่ใช้คูปองได้ของแถม */
    public function giftCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'coupon_gift_courses');
    }

    public function giftAssessments(): BelongsToMany
    {
        return $this->belongsToMany(Assessment::class, 'coupon_gift_assessments');
    }

    public function giftProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'coupon_gift_products');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function studentGroup(): string
    {
        return array_key_exists((string) $this->student_group, self::STUDENT_GROUPS) ? (string) $this->student_group : 'all';
    }

    public function studentGroupLabel(): string
    {
        return self::STUDENT_GROUPS[$this->studentGroup()];
    }

    public function purchaseType(): string
    {
        return array_key_exists((string) $this->purchase_type, self::PURCHASE_TYPES) ? (string) $this->purchase_type : 'any';
    }

    public function purchaseTypeLabel(): string
    {
        return self::PURCHASE_TYPES[$this->purchaseType()];
    }

    public function requiredPurchaseLabel(): string
    {
        $items = $this->requiredCourses->pluck('name')
            ->map(fn (string $name) => 'วิชา '.$name)
            ->merge($this->requiredProducts->pluck('name')->map(fn (string $name) => 'หนังสือ '.$name));

        return $items->isEmpty() ? 'ไม่ต้องเคยซื้อ' : 'ต้องเคยซื้ออย่างน้อย 1 รายการ: '.$items->join(', ');
    }

    public function requiredVideoLabel(): string
    {
        $titles = $this->requiredVideos->pluck('title');

        if ($titles->isEmpty()) {
            return 'ไม่ต้องดูวิดีโอก่อน';
        }

        $seconds = (int) $this->required_video_seconds;
        $amount = $seconds > 0 ? 'อย่างน้อย '.Video::secondsLabel($seconds) : 'จนจบ';

        return 'ต้องดู '.$titles->join(', ').' '.$amount.' ก่อน';
    }

    public function hasGifts(): bool
    {
        return $this->giftAssessments->isNotEmpty() || $this->giftProducts->isNotEmpty();
    }

    public function giftLabel(): string
    {
        $gifts = $this->giftAssessments->pluck('title')
            ->map(fn (string $title) => 'ข้อสอบ '.$title)
            ->merge($this->giftProducts->pluck('name')->map(fn (string $name) => 'หนังสือ '.$name));

        if ($gifts->isEmpty()) {
            return 'ไม่มีของแถม';
        }

        $triggers = $this->giftCourses->pluck('name');

        return ($triggers->isEmpty() ? 'แถม ' : 'ซื้อ '.$triggers->join(' หรือ ').' แถม ').$gifts->join(', ');
    }

    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'content_coupons');
    }

    public function discountLabel(): string
    {
        if ((float) $this->discount_value <= 0) {
            return 'ไม่มีส่วนลด (แถมอย่างเดียว)';
        }

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
