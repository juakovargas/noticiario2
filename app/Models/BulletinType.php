<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class BulletinType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'location_id',
        'news_category_id',
        'language_id',
        'default_prompt_profile_id',
        'ai_provider_id',
        'edition_type',
        'target_duration_seconds',
        'default_schedule_time',
        'default_run_frequency',
        'default_run_time',
        'default_run_days',
        'default_timezone',
        'default_schedule_is_active',
        'default_auto_run_pipeline',
        'default_auto_generate_ai_response',
        'default_auto_create_script',
        'default_auto_generate_metadata',
        'default_auto_extract_sources',
        'coverage_mode',
        'coverage_starts_offset_minutes',
        'coverage_ends_offset_minutes',
        'coverage_description',
        'include_future_agenda',
        'include_historical_context',
        'max_news_items',
        'min_news_items',
        'prompt_language',
        'output_mode',
        'is_active',
        'sort_order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'include_future_agenda' => 'boolean',
            'include_historical_context' => 'boolean',
            'default_run_days' => 'array',
            'default_schedule_is_active' => 'boolean',
            'default_auto_run_pipeline' => 'boolean',
            'default_auto_generate_ai_response' => 'boolean',
            'default_auto_create_script' => 'boolean',
            'default_auto_generate_metadata' => 'boolean',
            'default_auto_extract_sources' => 'boolean',
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

    public function promptProfile(): BelongsTo
    {
        return $this->belongsTo(PromptProfile::class, 'default_prompt_profile_id');
    }

    public function aiProvider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(EditorialSchedule::class);
    }

    public function editorialSchedules(): HasMany
    {
        return $this->schedules();
    }

    public function primarySchedule(): HasOne
    {
        return $this->hasOne(EditorialSchedule::class)->where('is_primary', true);
    }

    public function promptRuns(): HasMany
    {
        return $this->hasMany(BulletinPromptRun::class);
    }
}
