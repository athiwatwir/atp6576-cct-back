<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assessment extends Model
{
    use LogsActivity;
    use SoftDeletes;

    public function getAuditLabel(): string
    {
        return 'ข้อสอบ '.($this->code ?: '#'.$this->id).' '.$this->title;
    }

    protected $fillable = [
        'code',
        'title',
        'slug',
        'description',
        'thumbnail',
        'type',
        'status',
        'duration_minutes',
        'passing_score',
        'max_attempts',
        'is_independent',
        'price',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'passing_score' => 'decimal:2',
            'max_attempts' => 'integer',
            'is_independent' => 'boolean',
            'price' => 'decimal:2',
            'published_at' => 'datetime',
        ];
    }

    public function getEffectivePriceAttribute(): float
    {
        return (float) $this->price;
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'assessment_courses')
            ->withPivot('video_id', 'chapter_id');
    }

    public function assessmentCourses(): HasMany
    {
        return $this->hasMany(AssessmentCourse::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('sort_order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(AssessmentAttempt::class);
    }

    public function rankings(): HasMany
    {
        return $this->hasMany(Ranking::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeIndependent($query)
    {
        return $query->where('is_independent', true);
    }

    public function scopeForSale($query)
    {
        return $query->independent()
            ->where('type', 'primary_exam')
            ->where('status', 'published');
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return \App\Support\MediaStorage::url($this->thumbnail);
    }
}
