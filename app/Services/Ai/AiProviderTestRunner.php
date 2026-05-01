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
        $before = $this->rateLimiter->getAvailabilityContext($provider);
        $started = microtime(true);

        try {
            $response = $this->clientManager->generateText($provider, $prompt, ['grounding_enabled' => $groundingEnabled]);
            $duration = (int) ((microtime(true) - $started) * 1000);
            $after = $this->rateLimiter->getAvailabilityContext($provider->fresh());
            $log = AiRequestLog::query()->where('ai_provider_id', $provider->id)->latest('id')->first();

            return $this->buildResponse($provider,$testType,$groundingEnabled,$prompt,true,null,$duration,$before,$after,$log,[
                'status' => 200,
                'retry_after' => null,
                'text' => mb_substr((string) $response->text, 0, 500),
                'body_preview' => mb_substr((string) $response->text, 0, 500),
            ]);
        } catch (AiProviderException|Throwable $e) {
            $duration = (int) ((microtime(true) - $started) * 1000);
            $after = $this->rateLimiter->getAvailabilityContext($provider->fresh());
            $status = $e instanceof AiProviderException ? $e->statusCode : null;
            $internal = $e instanceof AiProviderException && $e->errorCode === 'internal_rate_limit';

            return $this->buildResponse($provider,$testType,$groundingEnabled,$prompt,false,$e,$duration,$before,$after,null,[
                'status' => $internal ? null : $status,
                'retry_after' => null,
                'error' => ['message' => $this->sanitizeValue($e->getMessage()), 'code' => $e instanceof AiProviderException ? $e->errorCode : 'provider_error'],
            ],$internal);
        }
    }

    private function buildResponse(AiProvider $provider, string $testType, bool $groundingEnabled, string $prompt, bool $ok, ?Throwable $error, int $duration, array $before, array $after, ?AiRequestLog $log, array $response, bool $internalBlocked = false): array
    {
        $requestWasSent = ! $internalBlocked;
        $providerStatus = $response['status'] ?? null;
        $result = $ok ? 'success' : ($internalBlocked ? 'blocked' : (($providerStatus === 429) ? 'rate_limited' : 'error'));

        $executionDriver = $provider->executionDriver();
        $clientAdapter = $provider->usesLaravelAiDriver() ? 'LaravelAiClientAdapter' : 'CustomAiClient';

        $trace = [
            'summary' => ['result' => $result, 'execution_driver' => $executionDriver, 'execution_driver_label' => $provider->usesLaravelAiDriver() ? 'Laravel AI SDK' : 'Custom Noticiario', 'client_adapter' => $clientAdapter, 'sdk_provider' => $provider->usesLaravelAiDriver() ? $provider->provider_type : null, 'sdk_model' => $provider->usesLaravelAiDriver() ? $provider->default_model : null, 'request_was_sent' => $requestWasSent, 'provider_status_code' => $providerStatus, 'duration_ms' => $duration, 'grounding' => $groundingEnabled, 'rate_limit_source' => data_get($after, 'source')],
            'rate_limiter' => [
                'blocked_before_request' => $internalBlocked,
                'status_before' => data_get($before, 'status'),
                'status_after' => data_get($after, 'status'),
                'internal_limiter_reason' => $internalBlocked ? (data_get($after, 'status') ?? data_get($before, 'status')) : null,
                'min_delay_wait' => (data_get($before, 'status') === 'min_delay_wait' || data_get($after, 'status') === 'min_delay_wait'),
                'retry_after_seconds' => data_get($after, 'retry_after_seconds') ?? data_get($before, 'retry_after_seconds'),
                'rate_limited_until_before' => data_get($before, 'rate_limited_until'),
                'rate_limited_until_after' => data_get($after, 'rate_limited_until'),
            ],
            'request' => ['method' => 'POST', 'endpoint' => rtrim((string) $provider->base_url, '/'), 'model' => $provider->default_model, 'prompt' => $prompt, 'payload' => ['prompt' => $prompt, 'max_tokens' => 60, 'grounding_enabled' => $groundingEnabled], 'headers' => ['Authorization' => '[REDACTED]']],
            'response' => $response,
        ];

        return [
            'ok' => $ok,
            'test_type' => $testType,
            'provider' => ['id' => $provider->id, 'name' => $provider->name, 'provider_type' => $provider->provider_type, 'model' => $provider->default_model, 'supports_grounding' => (bool) $provider->supports_grounding, 'capabilities' => $provider->capabilities, 'execution_driver' => $executionDriver],
            ...$trace,
            'ai_request_log' => $log ? ['id' => $log->id, 'status' => $log->status, 'error_code' => $log->error_code, 'estimated_cost' => $log->estimated_cost, 'input_tokens' => $log->input_tokens, 'output_tokens' => $log->output_tokens, 'total_tokens' => $log->total_tokens, 'created_at' => optional($log->created_at)?->toISOString(), 'url' => route('admin.ai-request-logs.show', $log)] : null,
            'safe_error_message' => $error ? $this->sanitizeValue($error->getMessage()) : null,
            'trace' => $this->sanitizeArray($trace),
        ];
    }

    private function sanitizeArray(array $data): array { foreach ($data as $k=>$v) { $data[$k]=is_array($v)?$this->sanitizeArray($v):$this->sanitizeValue($v,(string)$k);} return $data; }
    private function sanitizeValue(mixed $value, string $key = ''): mixed { if (!is_string($value)) return $value; $sensitive=['authorization','x-api-key','api_key','apikey','key','token','access_token','refresh_token','bearer','secret','password','client_secret']; foreach($sensitive as $needle){ if(stripos($key,$needle)!==false){return '[REDACTED]';}} return preg_replace('/(bearer\s+)[^\s]+/i','$1[REDACTED]',$value) ?? $value; }
}
