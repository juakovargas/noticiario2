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
        'gemini',
        'google_gemini',
    ];

    protected $fillable = [
        'name',
        'slug',
        'provider_type',
        'client_driver',
        'provider_category',
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
        'capabilities',
        'is_testing',
        'is_local',
        'supports_grounding',
        'supports_citations',
        'supports_streaming',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'timeout_seconds' => 'integer',
            'max_tokens' => 'integer',
            'temperature' => 'float',
            'cost_input_per_1k_tokens' => 'decimal:6',
            'cost_output_per_1k_tokens' => 'decimal:6',
            'daily_request_limit' => 'integer',
            'monthly_request_limit' => 'integer',
            'daily_cost_limit' => 'decimal:6',
            'monthly_cost_limit' => 'decimal:6',
            'metadata' => 'array',
            'capabilities' => 'array',
            'is_testing' => 'boolean',
            'is_local' => 'boolean',
            'supports_grounding' => 'boolean',
            'supports_citations' => 'boolean',
            'supports_streaming' => 'boolean',
        ];
    }

    public function temperatureForRequest(): ?float
    {
        if (blank($this->temperature)) {
            return null;
        }

        return max(0, min(2, (float) $this->temperature));
    }

    public function maxTokensForRequest(): ?int
    {
        if (blank($this->max_tokens)) {
            return null;
        }

        return max(1, (int) $this->max_tokens);
    }

    public function timeoutSecondsForRequest(): int
    {
        $timeout = blank($this->timeout_seconds) ? 60 : (int) $this->timeout_seconds;

        return max(5, min(300, $timeout));
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



    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function supportsCapability(string $capability): bool
    {
        return in_array($capability, $this->capabilities ?? [], true);
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
