<?php

namespace App\Models;

use App\Enums\OrderKind;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShippingStatus;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use LogsActivity;

    /** guest_token เป็นรหัสลับให้ผู้ซื้อแบบไม่สมัครสมาชิกใช้เปิดออเดอร์ ห้ามเก็บลง activity log */
    public array $auditExclude = ['updated_at', 'created_at', 'guest_token'];

    protected $hidden = ['guest_token'];

    public function getAuditLabel(): string
    {
        return 'ออเดอร์ '.($this->order_no ?: '#'.$this->id);
    }
    protected $fillable = [
        'order_no',
        'kind',
        'user_id',
        'guest_token',
        'guest_account_created',
        'coupon_id',
        'gift_for_order_id',
        'subtotal',
        'discount_amount',
        'shipping_amount',
        'total_amount',
        'status',
        'payment_status',
        'notes',
        'requires_shipping',
        'shipping_name',
        'shipping_phone',
        'shipping_address_line1',
        'shipping_address_line2',
        'shipping_subdistrict',
        'shipping_district',
        'shipping_province',
        'shipping_postal_code',
        'shipping_status',
        'shipping_carrier',
        'tracking_number',
        'shipped_at',
        'delivered_at',
        'paid_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'requires_shipping' => 'boolean',
            'guest_account_created' => 'boolean',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function isBook(): bool
    {
        return $this->kind === OrderKind::Book->value;
    }

    /** หน้ารายละเอียดในหลังบ้าน ออเดอร์หนังสือกับสมัครเรียนแยกหน้ากัน */
    public function adminUrl(): string
    {
        return route($this->isBook() ? 'orders.show' : 'enrollments.show', $this);
    }

    public function adminEditUrl(): string
    {
        return route($this->isBook() ? 'orders.edit' : 'enrollments.edit', $this);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function paymentLinks(): HasMany
    {
        return $this->hasMany(PaymentLink::class);
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class)->withTrashed();
    }

    /** ออเดอร์หนังสือแถมนี้มาจากคูปองของออเดอร์ไหน */
    public function giftFor(): BelongsTo
    {
        return $this->belongsTo(self::class, 'gift_for_order_id');
    }

    public function giftOrders(): HasMany
    {
        return $this->hasMany(self::class, 'gift_for_order_id');
    }

    public function couponUsages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * ออเดอร์ที่มีวิชาสถานะ TFH ไม่ส่งผ่าน API นักเรียนทุกกรณี
     */
    public function scopeVisibleInStudentApi(Builder $query): Builder
    {
        return $query->whereDoesntHave('items', fn (Builder $items) => $items->whereIn('course_id', self::tfhCourseIds()));
    }

    public function isHiddenFromStudentApi(): bool
    {
        return $this->items()->whereIn('course_id', self::tfhCourseIds())->exists();
    }

    private static function tfhCourseIds(): Builder
    {
        return Course::withTrashed()->where('status', Course::STATUS_TFH)->select('id');
    }

    public function getStatusLabelAttribute(): string
    {
        return OrderStatus::tryFrom((string) $this->status)?->label() ?? (string) $this->status;
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return PaymentStatus::tryFrom((string) $this->payment_status)?->label() ?? (string) $this->payment_status;
    }

    public function getShippingStatusLabelAttribute(): string
    {
        return ShippingStatus::tryFrom((string) $this->shipping_status)?->label() ?? (string) $this->shipping_status;
    }

    public function getShippingFullAddressAttribute(): string
    {
        return collect([
            $this->shipping_address_line1,
            $this->shipping_address_line2,
            $this->shipping_subdistrict,
            $this->shipping_district,
            $this->shipping_province,
            $this->shipping_postal_code,
        ])->filter()->implode(' ');
    }
}
