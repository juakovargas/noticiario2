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
        'status',
        'language',
        'intro',
        'body',
        'outro',
        'estimated_duration_seconds',
        'approved_at',
        'approved_by',
        'metadata',
        'review_status',
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
            'metadata' => 'array',
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

    public function editorialScheduleRuns(): HasMany
    {
        return $this->hasMany(EditorialScheduleRun::class);
    }
}
