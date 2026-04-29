<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiProvider extends Model
{
    use HasFactory, SoftDeletes;

    public const SUPPORTED_PROVIDER_TYPES = [
        'openai',
        'openrouter',
        'anthropic',
        'ollama',
        'custom_openai_compatible',
        'groq',
        'mock',
    ];

    protected $fillable = [
        'name',
        'slug',
        'provider_type',
        'client_driver',
        'base_url',
        'api_key_env_name',
        'default_model',
        'organization',
        'is_active',
        'is_default',
        'timeout_seconds',
        'max_tokens',
        'temperature',
        'cost_input_per_1k_tokens',
        'cost_output_per_1k_tokens',
        'daily_request_limit',
        'monthly_request_limit',
        'daily_cost_limit',
        'monthly_cost_limit',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'temperature' => 'decimal:2',
            'cost_input_per_1k_tokens' => 'decimal:6',
            'cost_output_per_1k_tokens' => 'decimal:6',
            'daily_cost_limit' => 'decimal:6',
            'monthly_cost_limit' => 'decimal:6',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AiProvider $provider): void {
            if (blank($provider->client_driver)) {
                $provider->client_driver = 'custom';
            }
        });
    }

    public function editorialRequests(): HasMany
    {
        return $this->hasMany(EditorialRequest::class);
    }

    public function aiRequestLogs(): HasMany
    {
        return $this->hasMany(AiRequestLog::class);
    }

    public function requiresApiKey(): bool
    {
        return $this->provider_type !== 'ollama' && $this->provider_type !== 'mock';
    }

    public function hasConfiguredApiKey(): bool
    {
        if (! $this->requiresApiKey()) {
            return true;
        }

        $envName = trim((string) $this->api_key_env_name);

        return $envName !== '' && filled(env($envName));
    }
}
