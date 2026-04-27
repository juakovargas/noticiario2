<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SourceReference extends Model
{
    use HasFactory, SoftDeletes;

    public const SOURCE_TYPES = ['web', 'official', 'agency', 'social', 'rss', 'manual', 'unknown'];

    public const VERIFICATION_STATUSES = ['pending', 'verified', 'weak', 'missing', 'broken', 'rejected', 'not_required'];

    protected $fillable = [
        'news_item_id',
        'script_id',
        'script_review_item_id',
        'editorial_schedule_run_id',
        'title',
        'source_name',
        'source_url',
        'source_type',
        'verification_status',
        'trust_level',
        'checked_by',
        'checked_at',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'checked_at' => 'datetime',
        ];
    }

    public function newsItem(): BelongsTo
    {
        return $this->belongsTo(NewsItem::class);
    }

    public function script(): BelongsTo
    {
        return $this->belongsTo(Script::class);
    }

    public function scriptReviewItem(): BelongsTo
    {
        return $this->belongsTo(ScriptReviewItem::class);
    }

    public function editorialScheduleRun(): BelongsTo
    {
        return $this->belongsTo(EditorialScheduleRun::class);
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
