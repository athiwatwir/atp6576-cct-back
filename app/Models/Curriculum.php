<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Curriculum extends Model
{
    use SoftDeletes;

    protected $table = 'curriculums';

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'short_description',
        'description',
        'thumbnail',
        'price',
        'sale_price',
        'status',
        'is_featured',
        'published_at',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'curriculum_courses')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'curriculum_subjects')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(Video::class, 'curriculum_videos')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'curriculum_documents')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function learningProgress(): HasMany
    {
        return $this->hasMany(LearningProgress::class);
    }

    public function promotions(): BelongsToMany
    {
        return $this->belongsToMany(Promotion::class, 'promotion_curriculums');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'content_curriculums');
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function trialAccess(): HasMany
    {
        return $this->hasMany(TrialAccess::class);
    }
}
