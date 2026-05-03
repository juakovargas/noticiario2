<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiProvider extends Model
{
    public const DRIVER_CUSTOM = 'custom';

    public const DRIVER_LARAVEL_AI = 'laravel_ai';
    use HasFactory, SoftDeletes;

    public const SUPPORTED_PROVIDER_TYPES = [
        'text',
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

        'requests_per_minute_limit',
        'requests_per_day_limit',
        'tokens_per_minute_limit',
        'min_seconds_between_requests',
        'retry_on_rate_limit',
        'max_retries',
        'initial_retry_delay_seconds',
        'max_retry_delay_seconds',
        'backoff_multiplier',
        'jitter_enabled',
        'last_request_at',
        'rate_limited_until',
        'rate_limit_metadata',
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

            'requests_per_minute_limit' => 'integer',
            'requests_per_day_limit' => 'integer',
            'tokens_per_minute_limit' => 'integer',
            'min_seconds_between_requests' => 'integer',
            'retry_on_rate_limit' => 'boolean',
            'max_retries' => 'integer',
            'initial_retry_delay_seconds' => 'integer',
            'max_retry_delay_seconds' => 'integer',
            'backoff_multiplier' => 'float',
            'jitter_enabled' => 'boolean',
            'last_request_at' => 'datetime',
            'rate_limited_until' => 'datetime',
            'rate_limit_metadata' => 'array',
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
            if (blank($provider->client_driver) || ! in_array($provider->client_driver, [self::DRIVER_CUSTOM, self::DRIVER_LARAVEL_AI], true)) {
                $provider->client_driver = self::DRIVER_CUSTOM;
            }
        });
    }


    public function executionDriver(): string
    {
        return in_array($this->client_driver, [self::DRIVER_CUSTOM, self::DRIVER_LARAVEL_AI], true)
            ? $this->client_driver
            : self::DRIVER_CUSTOM;
    }

    public function usesLaravelAiDriver(): bool
    {
        return $this->executionDriver() === self::DRIVER_LARAVEL_AI;
    }

    public function usesCustomDriver(): bool
    {
        return ! $this->usesLaravelAiDriver();
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

    public function apiKeyEnvName(): ?string
    {
        $envName = trim((string) $this->api_key_env_name);

        if ($envName !== '') {
            return $envName;
        }

        return match ($this->provider_type) {
            'text' => match ($this->slug) {
                'deepseek-v4' => 'DEEPSEEK_API_KEY',
                default => null,
            },
            'gemini', 'google_gemini' => 'GEMINI_API_KEY',
            'groq' => 'GROQ_API_KEY',
            'openai' => 'OPENAI_API_KEY',
            'openrouter' => 'OPENROUTER_API_KEY',
            'anthropic' => 'ANTHROPIC_API_KEY',
            default => null,
        };
    }

    public function apiKeyValue(): ?string
    {
        $envName = $this->apiKeyEnvName();

        if (! $envName) {
            return null;
        }

        $configValue = match ($envName) {
            'GEMINI_API_KEY' => config('services.gemini.key'),
            'GROQ_API_KEY' => config('services.groq.key'),
            'OPENAI_API_KEY' => config('services.openai.key'),
            'OPENROUTER_API_KEY' => config('services.openrouter.key'),
            'ANTHROPIC_API_KEY' => config('services.anthropic.key'),
            'DEEPSEEK_API_KEY' => config('services.deepseek.key'),
            default => null,
        };

        $value = $configValue ?: env($envName) ?: getenv($envName) ?: ($_ENV[$envName] ?? null) ?: ($_SERVER[$envName] ?? null);

        return filled($value) ? (string) $value : null;
    }

    public function hasConfiguredApiKey(): bool
    {
        if (! $this->requiresApiKey()) {
            return true;
        }

        return filled($this->apiKeyValue());
    }
}
