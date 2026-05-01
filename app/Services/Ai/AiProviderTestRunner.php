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
            'short_script' => 'Eres redactor de informativos breves. Escribe un guion periodístico natural de unos 20 segundos sobre una actualización general de actualidad. No inventes datos concretos. Devuelve solo el texto final para locución.',
            'grounded_search' => 'Busca una noticia deportiva reciente de España y responde en una frase con una fuente.',
            default => 'Responde solo OK.',
        };

        $groundingEnabled = $testType === 'grounded_search';
        $groundingSupported = ! ($groundingEnabled && $provider->usesLaravelAiDriver());
        $before = $this->rateLimiter->getAvailabilityContext($provider);
        $started = microtime(true);

        try {
            $response = $this->clientManager->generateText($provider, $prompt, ['grounding_enabled' => $groundingSupported]);
            $duration = (int) ((microtime(true) - $started) * 1000);
            $after = $this->rateLimiter->getAvailabilityContext($provider->fresh());
            $log = $this->createDiagnosticLog($provider, $testType, $prompt, true, null, $duration, ['status' => 200, 'text' => $response->text]);

            return $this->buildResponse($provider, $testType, $groundingEnabled, $groundingSupported, $prompt, true, null, $duration, $before, $after, $log, [
                'status' => 200,
                'retry_after' => null,
                'text' => mb_substr((string) $response->text, 0, 500),
                'body_preview' => mb_substr((string) $response->text, 0, 500),
            ]);
        } catch (Throwable $e) {
            $duration = (int) ((microtime(true) - $started) * 1000);
            $after = $this->rateLimiter->getAvailabilityContext($provider->fresh());
            $status = $e instanceof AiProviderException ? $e->statusCode : null;
            $internal = $e instanceof AiProviderException && $e->errorCode === 'internal_rate_limit';
            $log = $this->createDiagnosticLog($provider, $testType, $prompt, false, $e, $duration, ['status' => $status]);

            return $this->buildResponse($provider, $testType, $groundingEnabled, $groundingSupported, $prompt, false, $e, $duration, $before, $after, $log, [
                'status' => $internal ? null : $status,
                'retry_after' => null,
                'error' => ['message' => $this->sanitizeValue($e->getMessage()), 'code' => $e instanceof AiProviderException ? $e->errorCode : 'provider_error'],
            ], $internal);
        }
    }

    private function buildResponse(AiProvider $provider, string $testType, bool $groundingEnabled, bool $groundingSupported, string $prompt, bool $ok, ?Throwable $error, int $duration, array $before, array $after, ?AiRequestLog $log, array $response, bool $internalBlocked = false): array
    {
        $requestWasSent = $ok || ($error instanceof AiProviderException ? $error->requestWasSent : false);
        $providerStatus = $response['status'] ?? null;
        $trace = ['summary' => [
            'result' => $ok ? 'success' : ($internalBlocked ? 'blocked' : (($providerStatus === 429) ? 'rate_limited' : 'error')),
            'execution_driver' => $provider->executionDriver(),
            'execution_driver_label' => $provider->usesLaravelAiDriver() ? 'Laravel AI SDK' : 'Custom Noticiario',
            'client_adapter' => $provider->usesLaravelAiDriver() ? 'LaravelAiClientAdapter' : 'CustomAiClient',
            'sdk_provider' => $provider->usesLaravelAiDriver() ? $provider->provider_type : null,
            'sdk_model' => $provider->usesLaravelAiDriver() ? $provider->default_model : null,
            'sdk_installed' => class_exists(\Laravel\Ai\Facades\Ai::class),
            'sdk_configured' => (bool) (($provider->api_key_env_name ?? '') !== ''),
            'sdk_config_key_present' => (($provider->api_key_env_name ?? '') !== '') && (bool) env((string) $provider->api_key_env_name),
            'sdk_failure_stage' => $this->resolveFailureStage($error),
            'request_was_sent' => $requestWasSent,
            'provider_status_code' => $providerStatus,
            'request_failed_before_http_response' => ! $ok && ! $requestWasSent,
            'duration_ms' => $duration,
            'provider_supports_grounding' => (bool) $provider->supports_grounding,
            'driver_supports_grounding' => $groundingSupported,
            'rate_limit_source' => $internalBlocked ? 'internal_rate_limiter' : (($providerStatus === 429 && $requestWasSent) ? 'provider_rate_limit' : data_get($after, 'source')),
            'sdk_exception_class' => $error ? class_basename($error) : null,
            'sdk_exception_message_safe' => $error ? $this->sanitizeValue($error->getMessage()) : null,
            'sdk_exception_previous_class' => $error?->getPrevious() ? class_basename($error->getPrevious()) : null,
            'sdk_exception_previous_message_safe' => $error?->getPrevious() ? $this->sanitizeValue($error->getPrevious()->getMessage()) : null,
        ]];

        return ['ok' => $ok, 'test_type' => $testType, ...$trace, 'request' => ['normalized_payload' => ['prompt' => $prompt, 'grounding_enabled' => $groundingEnabled]], 'response' => $response, 'ai_request_log' => $log ? ['id' => $log->id] : null, 'trace' => $this->sanitizeArray($trace)];
    }

    private function resolveFailureStage(?Throwable $error): ?string
    {
        if (! $error instanceof AiProviderException) return null;
        return match ($error->errorCode) {
            'sdk_package_missing' => 'package_missing',
            'sdk_config_missing' => 'config_missing',
            'sdk_key_missing' => 'key_missing',
            'sdk_adapter_build_failed' => 'adapter_build_failed',
            'sdk_response_parse_failed' => 'response_parse_failed',
            default => 'sdk_call_failed',
        };
    }

    private function createDiagnosticLog(AiProvider $provider, string $testType, string $prompt, bool $ok, ?Throwable $error, int $duration, array $response): AiRequestLog
    {
        return AiRequestLog::query()->create(['ai_provider_id' => $provider->id, 'model' => $provider->default_model, 'status' => $ok ? 'success' : 'failed', 'request_type' => 'diagnostic_test', 'prompt_hash' => hash('sha256', $prompt), 'prompt_preview' => mb_substr($prompt, 0, 500), 'response_preview' => $ok ? mb_substr((string) data_get($response, 'text', ''), 0, 500) : null, 'duration_ms' => $duration, 'error_message' => $error ? $this->sanitizeValue($error->getMessage()) : null, 'error_code' => $error instanceof AiProviderException ? $error->errorCode : null, 'provider_status_code' => (int) data_get($response, 'status', 0) ?: null, 'metadata' => ['diagnostic_test' => true, 'test_type' => $testType, 'execution_driver' => $provider->executionDriver(), 'client_adapter' => $provider->usesLaravelAiDriver() ? 'LaravelAiClientAdapter' : 'CustomAiClient', 'request_was_sent' => $ok ? true : ($error instanceof AiProviderException ? $error->requestWasSent : false), 'sdk_failure_stage' => $this->resolveFailureStage($error)], 'started_at' => now()->subMilliseconds(max($duration, 0)), 'completed_at' => now()]);
    }

    private function sanitizeArray(array $data): array { foreach ($data as $k => $v) { $data[$k] = is_array($v) ? $this->sanitizeArray($v) : $this->sanitizeValue($v, (string) $k); } return $data; }
    private function sanitizeValue(mixed $value, string $key = ''): mixed { if (! is_string($value)) return $value; if (preg_match('/authorization|api[_-]?key|token|secret|password/i', $key)) return '[REDACTED]'; return preg_replace('/(bearer\s+)[^\s]+/i', '$1[REDACTED]', $value) ?? $value; }
}
