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
        'bulletin_type_id',
        'edition_type',
        'frequency_type',
        'run_frequency',
        'scheduled_time',
        'run_time',
        'scheduled_date',
        'weekdays',
        'run_days',
        'timezone',
        'is_primary',
        'next_run_at',
        'last_run_at',
        'target_duration_seconds',
        'tone',
        'manual_ai_mode',
        'is_active',
        'auto_create_prompt_run',
        'auto_generate_prompt',
        'auto_run_pipeline',
        'auto_generate_ai_response',
        'auto_create_script',
        'auto_generate_metadata',
        'auto_extract_sources',
        'editorial_instructions',
        'output_instructions',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'weekdays' => 'array',
            'run_days' => 'array',
            'next_run_at' => 'datetime',
            'last_run_at' => 'datetime',
            'last_success_at' => 'datetime',
            'last_failure_at' => 'datetime',
            'is_primary' => 'boolean',
            'manual_ai_mode' => 'boolean',
            'is_active' => 'boolean',
            'auto_create_prompt_run' => 'boolean',
            'auto_generate_prompt' => 'boolean',
            'auto_run_pipeline' => 'boolean',
            'auto_generate_ai_response' => 'boolean',
            'auto_create_script' => 'boolean',
            'auto_generate_metadata' => 'boolean',
            'auto_extract_sources' => 'boolean',
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

    public function bulletinType(): BelongsTo
    {
        return $this->belongsTo(BulletinType::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(EditorialScheduleRun::class);
    }
}
