<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BulletinPromptRun extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bulletin_type_id',
        'prompt_profile_id',
        'edition_id',
        'script_id',
        'editorial_schedule_id',
        'editorial_schedule_run_id',
        'created_by',
        'title',
        'scheduled_for',
        'status',
        'generated_prompt',
        'ai_response_text',
        'parsed_response',
        'prompt_generated_at',
        'response_received_at',
        'script_created_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime',
            'parsed_response' => 'array',
            'prompt_generated_at' => 'datetime',
            'response_received_at' => 'datetime',
            'script_created_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function bulletinType(): BelongsTo
    {
        return $this->belongsTo(BulletinType::class);
    }

    public function promptProfile(): BelongsTo
    {
        return $this->belongsTo(PromptProfile::class);
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function script(): BelongsTo
    {
        return $this->belongsTo(Script::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editorialSchedule(): BelongsTo
    {
        return $this->belongsTo(EditorialSchedule::class);
    }

    public function editorialScheduleRun(): BelongsTo
    {
        return $this->belongsTo(EditorialScheduleRun::class);
    }

    public function sourceReferences(): HasMany
    {
        return $this->hasMany(SourceReference::class);
    }

    public function aiRequestLogs(): HasMany
    {
        return $this->hasMany(AiRequestLog::class);
    }
}
