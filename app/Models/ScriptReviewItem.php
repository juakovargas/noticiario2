<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScriptReviewItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'script_id',
        'news_item_id',
        'sort_order',
        'type',
        'title',
        'content',
        'source_hints',
        'verification_status',
        'verification_notes',
        'required_action',
        'reviewed_by',
        'reviewed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'source_hints' => 'array',
            'metadata' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function script(): BelongsTo
    {
        return $this->belongsTo(Script::class);
    }

    public function newsItem(): BelongsTo
    {
        return $this->belongsTo(NewsItem::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
