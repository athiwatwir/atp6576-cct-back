<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'is_free',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'is_free' => 'boolean',
        ];
    }

    public function chapters(): BelongsToMany
    {
        return $this->belongsToMany(Chapter::class, 'chapter_documents')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    public function curriculums(): BelongsToMany
    {
        return $this->belongsToMany(Curriculum::class, 'curriculum_documents')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }
}
