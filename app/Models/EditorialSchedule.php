<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EditorialSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'location_id',
        'news_category_id',
        'language_id',
        'editorial_template_id',
        'ai_prompt_template_id',
        'edition_type',
        'frequency_type',
        'scheduled_time',
        'scheduled_date',
        'weekdays',
        'timezone',
        'target_duration_seconds',
        'tone',
        'manual_ai_mode',
        'is_active',
        'editorial_instructions',
        'output_instructions',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'weekdays' => 'array',
            'manual_ai_mode' => 'boolean',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
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

    public function editorialTemplate(): BelongsTo
    {
        return $this->belongsTo(EditorialTemplate::class);
    }

    public function aiPromptTemplate(): BelongsTo
    {
        return $this->belongsTo(AiPromptTemplate::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(EditorialScheduleRun::class);
    }
}
