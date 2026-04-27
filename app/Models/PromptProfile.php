<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromptProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'happiness_level',
        'optimism_level',
        'seriousness_level',
        'humor_level',
        'irony_level',
        'formality_level',
        'negativity_tolerance',
        'controversy_tolerance',
        'source_strictness_level',
        'target_audience',
        'presenter_style',
        'forbidden_topics',
        'preferred_topics',
        'style_instructions',
        'fact_checking_instructions',
        'output_instructions',
        'is_active',
        'is_default',
        'sort_order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'forbidden_topics' => 'array',
            'preferred_topics' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function bulletinTypes(): HasMany
    {
        return $this->hasMany(BulletinType::class, 'default_prompt_profile_id');
    }

    public function promptRuns(): HasMany
    {
        return $this->hasMany(BulletinPromptRun::class);
    }
}
