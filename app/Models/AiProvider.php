<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiProvider extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'provider_type',
        'base_url',
        'api_key_env',
        'default_model',
        'supports_web_search',
        'supports_json_mode',
        'is_active',
        'is_default',
        'monthly_budget_cents',
        'cost_per_1k_input_tokens_cents',
        'cost_per_1k_output_tokens_cents',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'supports_web_search' => 'boolean',
            'supports_json_mode' => 'boolean',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function editorialRequests(): HasMany
    {
        return $this->hasMany(EditorialRequest::class);
    }
}
