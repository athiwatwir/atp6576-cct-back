<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Support\MediaStorage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Curriculum extends Model
{
    use LogsActivity;
    use SoftDeletes;

    public array $auditExclude = [
        'updated_at',
        'created_at',
        'description',
        'short_description',
    ];

    public function getAuditLabel(): string
    {
        return 'หลักสูตร '.$this->name;
    }

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

    public function chapters(): BelongsToMany
    {
        return $this->belongsToMany(Chapter::class, 'curriculum_chapters')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    public function assessments(): BelongsToMany
    {
        return $this->belongsToMany(Assessment::class, 'curriculum_assessments')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'curriculum_products')
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
}
