<?php

namespace App\Services\Ai;

use App\Models\AiProvider;
use App\Models\AiRequestLog;
use App\Services\Ai\Exceptions\AiProviderException;
use Throwable;

class AiProviderTestRunner
{
    public function __construct(
        private readonly AiClientManager $clientManager,
        private readonly AiProviderRateLimiter $rateLimiter,
    ) {}

    public function run(AiProvider $provider, string $testType): array
    {
        $prompt = match ($testType) {
            'short_script' => 'Escribe en español un guion de prueba de 20 segundos sobre una noticia ficticia. No uses datos reales.',
            'grounded_search' => 'Busca una noticia deportiva reciente de España y responde en una frase con una fuente.',
            default => 'Responde solo OK.',
        };

        $groundingEnabled = $testType === 'grounded_search';
        $before = $this->rateLimiter->getAvailabilityContext($provider);
        $started = microtime(true);

        try {
            $response = $this->clientManager->generateText($provider, $prompt, ['grounding_enabled' => $groundingEnabled]);
            $duration = (int) ((microtime(true) - $started) * 1000);
            $after = $this->rateLimiter->getAvailabilityContext($provider->fresh());
            $log = AiRequestLog::query()->where('ai_provider_id', $provider->id)->latest('id')->first();

            return [
                'test_type' => $testType,
                'provider_id' => $provider->id,
                'provider_name' => $provider->name,
                'provider_slug' => $provider->slug,
                'model' => $provider->default_model,
                'grounding_enabled' => $groundingEnabled,
                'request_was_sent' => true,
                'blocked_by_internal_limiter' => false,
                'endpoint' => rtrim((string) $provider->base_url, '/'),
                'method' => 'POST',
                'payload_preview' => ['prompt' => mb_substr($prompt, 0, 180)],
                'http_status' => 200,
                'retry_after_header' => null,
                'duration_ms' => $duration,
                'success' => true,
                'response_preview' => mb_substr((string) $response->text, 0, 300),
                'safe_error_message' => null,
                'error_code' => null,
                'ai_request_log_id' => $log?->id,
                'ai_request_log_status' => $log?->status,
                'rate_limiter_before' => $before,
                'rate_limiter_after' => $after,
                'created_at' => now()->toISOString(),
            ];
        } catch (AiProviderException|Throwable $e) {
            $duration = (int) ((microtime(true) - $started) * 1000);
            $after = $this->rateLimiter->getAvailabilityContext($provider->fresh());
            $status = $e instanceof AiProviderException ? $e->statusCode : null;
            $internal = $e instanceof AiProviderException && $e->errorCode === 'internal_rate_limit';

            return [
                'test_type' => $testType,
                'provider_id' => $provider->id,
                'provider_name' => $provider->name,
                'provider_slug' => $provider->slug,
                'model' => $provider->default_model,
                'grounding_enabled' => $groundingEnabled,
                'request_was_sent' => ! $internal,
                'blocked_by_internal_limiter' => $internal,
                'endpoint' => rtrim((string) $provider->base_url, '/'),
                'method' => 'POST',
                'payload_preview' => ['prompt' => mb_substr($prompt, 0, 180)],
                'http_status' => $internal ? null : $status,
                'retry_after_header' => null,
                'duration_ms' => $duration,
                'success' => false,
                'response_preview' => null,
                'safe_error_message' => $internal
                    ? 'No se llamó al proveedor porque está bloqueado internamente hasta '.(data_get($after, 'rate_limited_until') ?? now()->toISOString()).'.'
                    : (($status === 429) ? 'Gemini devolvió 429 Too Many Requests.' : $e->getMessage()),
                'error_code' => $e instanceof AiProviderException ? $e->errorCode : 'provider_error',
                'ai_request_log_id' => null,
                'ai_request_log_status' => null,
                'rate_limiter_before' => $before,
                'rate_limiter_after' => $after,
                'created_at' => now()->toISOString(),
            ];
        }
    }
}
