<?php

namespace App\Models;

use App\Enums\BannerPlacement;
use App\Support\MediaStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Content extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'placement',
        'title',
        'slug',
        'excerpt',
        'content',
        'image',
        'link_url',
        'status',
        'sort_order',
        'published_at',
        'expired_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'expired_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function scopeBanners(Builder $query): Builder
    {
        return $query->where('type', 'banner');
    }

    public function scopeArticles(Builder $query): Builder
    {
        return $query->where('type', 'article');
    }

    public function scopeVisible(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('status', 'published')
            ->where(function (Builder $inner) use ($now) {
                $inner->whereNull('published_at')->orWhere('published_at', '<=', $now);
            })
            ->where(function (Builder $inner) use ($now) {
                $inner->whereNull('expired_at')->orWhere('expired_at', '>=', $now);
            });
    }

    public function isLive(): bool
    {
        if ($this->status !== 'published') {
            return false;
        }

        if ($this->published_at && $this->published_at->isFuture()) {
            return false;
        }

        if ($this->expired_at && $this->expired_at->isPast()) {
            return false;
        }

        return true;
    }

    public function getImageUrlAttribute(): ?string
    {
        return MediaStorage::url($this->image);
    }

    public function getPlacementLabelAttribute(): string
    {
        return BannerPlacement::labelFor($this->placement);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'content_courses');
    }

    public function curriculums(): BelongsToMany
    {
        return $this->belongsToMany(Curriculum::class, 'content_curriculums');
    }

    public function promotions(): BelongsToMany
    {
        return $this->belongsToMany(Promotion::class, 'content_promotions');
    }

    public function coupons(): BelongsToMany
    {
        return $this->belongsToMany(Coupon::class, 'content_coupons');
    }

    public function stats(): HasMany
    {
        return $this->hasMany(ContentStat::class);
    }
}
