<?php

namespace App\Services\Ai;

use App\Models\AiProvider;
use App\Models\AiRequestLog;

class AiRequestTraceBuilder
{
    public function buildProviderTrace(AiProvider $provider, ?AiRequestLog $log = null, bool $groundingEnabled = false): array
    {
        $availability = app(AiProviderRateLimiter::class)->getAvailabilityContext($provider);

        return [
            'provider' => [
                'id' => $provider->id,
                'name' => $provider->name,
                'slug' => $provider->slug,
                'type' => $provider->provider_type,
                'category' => $provider->provider_category,
                'is_active' => (bool) $provider->is_active,
                'is_default' => (bool) $provider->is_default,
            ],
            'model' => $provider->default_model,
            'api_key_env_name' => $provider->api_key_env_name,
            'api_key_configured' => $provider->hasConfiguredApiKey(),
            'supports_grounding' => (bool) $provider->supports_grounding,
            'supports_citations' => (bool) $provider->supports_citations,
            'grounding_enabled_for_request' => $groundingEnabled,
            'availability' => $availability,
            'rate_limited_until' => optional($provider->rate_limited_until)?->toISOString(),
            'last_request' => $log ? [
                'status' => $log->status,
                'request_was_sent' => (bool) data_get($log->metadata, 'request_was_sent', false),
                'provider_status_code' => $log->provider_status_code,
                'error_code' => $log->error_code,
                'safe_error_message' => $log->error_message,
            ] : null,
        ];
    }
}
