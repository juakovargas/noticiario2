<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Script extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'edition_id',
        'title',
        'final_title',
        'production_name',
        'status',
        'language',
        'intro',
        'body',
        'outro',
        'estimated_duration_seconds',
        'bulletin_prompt_run_id',
        'public_description',
        'short_description',
        'hashtags',
        'social_copy',
        'target_platforms',
        'seo_title',
        'seo_description',
        'approved_at',
        'approved_by',
        'metadata',
        'review_status',
        'production_status',
        'ready_for_production_at',
        'ready_for_production_by',
        'review_notes',
        'fact_check_notes',
        'reviewed_by',
        'reviewed_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'rejected_at' => 'datetime',
            'ready_for_production_at' => 'datetime',
            'metadata' => 'array',
            'hashtags' => 'array',
            'target_platforms' => 'array',
        ];
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }


    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function reviewItems(): HasMany
    {
        return $this->hasMany(ScriptReviewItem::class)->orderBy('sort_order');
    }

    public function bulletinPromptRun(): BelongsTo
    {
        return $this->belongsTo(BulletinPromptRun::class, 'bulletin_prompt_run_id');
    }

    public function readyForProductionBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ready_for_production_by');
    }

    public function editorialScheduleRuns(): HasMany
    {
        return $this->hasMany(EditorialScheduleRun::class);
    }

    public function bulletinPromptRuns(): HasMany
    {
        return $this->hasMany(BulletinPromptRun::class);
    }

    public function sourceReferences(): HasMany
    {
        return $this->hasMany(SourceReference::class);
    }

    public function audioRenders(): HasMany
    {
        return $this->hasMany(ScriptAudioRender::class);
    }

    public function bulletinType(): BelongsTo
    {
        return $this->belongsTo(BulletinType::class);
    }
}
