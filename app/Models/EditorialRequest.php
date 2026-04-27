<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EditorialRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'requested_by',
        'ai_provider_id',
        'ai_prompt_template_id',
        'location_id',
        'news_category_id',
        'language_id',
        'edition_id',
        'title',
        'edition_type',
        'target_duration_seconds',
        'editorial_instructions',
        'status',
        'prompt_snapshot',
        'response_snapshot',
        'parsed_response',
        'error_message',
        'requested_at',
        'completed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'parsed_response' => 'array',
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function aiProvider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class);
    }

    public function aiPromptTemplate(): BelongsTo
    {
        return $this->belongsTo(AiPromptTemplate::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function newsCategory(): BelongsTo
    {
        return $this->belongsTo(NewsCategory::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(EditorialRequestCandidate::class);
    }
}
