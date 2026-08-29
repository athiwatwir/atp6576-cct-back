<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentStat extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'content_id',
        'user_id',
        'event_type',
        'ip_address',
        'created_at',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
