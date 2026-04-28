<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SourceReference extends Model
{
    use HasFactory, SoftDeletes;

    public const SOURCE_TYPES = ['official', 'agency', 'media', 'web', 'social', 'rss', 'manual', 'unknown'];

    public const VERIFICATION_STATUSES = ['pending', 'verified', 'weak', 'missing', 'broken', 'rejected', 'not_required'];

    protected $fillable = [
        'bulletin_prompt_run_id',
        'news_item_id',
        'script_id',
        'edition_id',
        'script_review_item_id',
        'editorial_schedule_run_id',
        'title',
        'source_name',
        'source_url',
        'source_domain',
        'source_type',
        'verification_status',
        'trust_level',
        'checked_by',
        'checked_at',
        'archived_at',
        'archived_by',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'checked_at' => 'datetime',
            'archived_at' => 'datetime',
            'trust_level' => 'integer',
        ];
    }

    public function scopeWithoutArchived(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeOnlyArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('verification_status', 'verified');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('verification_status', 'pending');
    }

    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->whereIn('verification_status', ['pending', 'weak', 'missing', 'broken', 'rejected']);
    }

    public function bulletinPromptRun(): BelongsTo
    {
        return $this->belongsTo(BulletinPromptRun::class);
    }

    public function newsItem(): BelongsTo
    {
        return $this->belongsTo(NewsItem::class);
    }

    public function script(): BelongsTo
    {
        return $this->belongsTo(Script::class);
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
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

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }
}
