<?php

namespace App\Services\Ai\Clients;

use App\Models\AiProvider;
use App\Services\Ai\AiProviderRateLimiter;
use App\Services\Ai\Contracts\AiClient;
use App\Services\Ai\Data\AiResponseData;
use App\Services\Ai\Exceptions\AiProviderException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GeminiClient implements AiClient
{
    public function __construct(private readonly AiProviderRateLimiter $rateLimiter = new AiProviderRateLimiter()) {}

    public function generateText(AiProvider $provider, string $prompt, array $options = []): AiResponseData
    {
        $key = trim((string) $provider->apiKeyValue());
        if ($key === '') throw new AiProviderException('Environment key is not configured.', null, false, null, false, 'api_key_missing');

        $model = trim((string) (($options['model'] ?? $provider->default_model) ?: 'gemini-3-flash-preview'));
        if ($model === '') {
            throw new AiProviderException('Model is required for Gemini requests.', null, false, null, false, 'model_missing');
        }

        $availability = $this->rateLimiter->getAvailabilityContext($provider);
        if (($availability['status'] ?? 'available') !== 'available') {
            $retryAfter = (int) ($availability['retry_after_seconds'] ?? 1);
            $msg = ($availability['status'] ?? null) === 'min_delay_wait'
                ? 'The provider was not called because the minimum delay between requests is still active.'
                : 'The provider was not called because it is temporarily rate-limited until '.($availability['rate_limited_until'] ?? now()->toISOString()).'.';
            throw new AiProviderException($msg, null, true, $retryAfter, false, 'internal_rate_limit');
        }

        $url = rtrim((string)($provider->base_url ?: 'https://generativelanguage.googleapis.com/v1beta'), '/')."/models/{$model}:generateContent";
        $payload = ['contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]], 'generationConfig' => ['maxOutputTokens' => (int) ($provider->max_tokens ?: 60)]];

        if (($options['payload_style'] ?? null) === 'gemini_official_minimal') {
            $model = 'gemini-3-flash-preview';
            $url = rtrim((string)($provider->base_url ?: 'https://generativelanguage.googleapis.com/v1beta'), '/')."/models/{$model}:generateContent";
            $payload = ['contents' => [[ 'parts' => [['text' => $prompt]] ]]];
        }
        if (($options['grounding_enabled'] ?? false) && ($provider->supports_grounding || $provider->supportsCapability('google_search_grounding'))) $payload['tools'] = [['google_search' => (object)[]]];

        $maxRetries = $provider->retry_on_rate_limit ? (int) ($provider->max_retries ?? 2) : 0;
        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            $this->rateLimiter->markRequestStarted($provider);
            $response = Http::timeout($provider->timeoutSecondsForRequest())
                ->withHeaders(['x-goog-api-key' => $key, 'Content-Type' => 'application/json'])
                ->post($url, $payload);
            if ($response->successful()) {
                $this->rateLimiter->markRequestFinished($provider);
                $json = $response->json();
                $text = data_get($json, 'candidates.0.content.parts.0.text', '');
                return new AiResponseData($text, $json, $provider->provider_type, $model,
                    data_get($json,'usageMetadata.promptTokenCount'), data_get($json,'usageMetadata.candidatesTokenCount'), data_get($json,'usageMetadata.totalTokenCount'),
                    data_get($json,'candidates.0.finishReason'), null, ['grounding' => data_get($json,'candidates.0.groundingMetadata')]);
            }

            if ($this->rateLimiter->isRateLimitStatus($response->status())) {
                $retryAfterHeader = $this->rateLimiter->parseRetryAfterSeconds($response);
                $retryAfter = $retryAfterHeader ?? $this->rateLimiter->calculateBackoffDelay($provider, $attempt + 1);
                $metadata = [
                    'source' => 'provider_response', 'provider_name' => $provider->name, 'provider_slug' => $provider->slug,
                    'provider_status_code' => 429, 'attempt' => $attempt + 1, 'max_retries' => $maxRetries,
                    'retry_after_seconds' => $retryAfter, 'response_retry_after_header' => $response->header('Retry-After'),
                    'last_request_at' => now()->toISOString(), 'min_seconds_between_requests' => $provider->min_seconds_between_requests,
                    'requests_per_minute_limit' => $provider->requests_per_minute_limit, 'message_key' => 'provider_rate_limit',
                ];
                $this->rateLimiter->markRateLimited($provider, $retryAfter, $metadata);
                if ($attempt < $maxRetries && $retryAfter <= 10) { sleep($retryAfter); continue; }
                $details = $this->parseErrorPayload($response);
                $msg = (string) ($details['google_error']['message'] ?? 'Gemini returned HTTP 429. Check the Google error details below.');
                throw new AiProviderException($msg, 429, true, $retryAfter, true, 'provider_rate_limit', ['response' => $details]);
            }

            $details = $this->parseErrorPayload($response);
            $msg = (string) ($details['google_error']['message'] ?? ('Gemini request failed with status '.$response->status()));
            throw new AiProviderException($msg, $response->status(), $response->status() >= 500, null, true, 'provider_error', ['response' => $details]);
        }

        throw new AiProviderException('AI request failed.', null, false, null, false, 'provider_error');
    }

    private function parseErrorPayload($response): array
    {
        $rawBody = (string) $response->body();
        $json = $response->json();
        $error = is_array($json) ? data_get($json, 'error', []) : [];

        return [
            'status' => $response->status(),
            'retry_after' => $response->header('Retry-After'),
            'google_error' => [
                'code' => data_get($error, 'code'),
                'status' => data_get($error, 'status'),
                'message' => data_get($error, 'message'),
                'details' => data_get($error, 'details'),
                'quota_metric' => data_get($error, 'details.0.violations.0.quotaMetric') ?? data_get($error, 'details.0.quotaMetric'),
                'quota_id' => data_get($error, 'details.0.violations.0.quotaId') ?? data_get($error, 'details.0.quotaId'),
                'retry_delay' => data_get($error, 'details.0.retryDelay') ?? data_get($error, 'details.1.retryDelay'),
            ],
            'raw_body_sanitized' => Str::limit(preg_replace('/(key=|x-goog-api-key\s*[:=]\s*)([^\s\",]+)/i', '$1[REDACTED]', $rawBody) ?? $rawBody, 5000),
            'headers_sanitized' => [
                'retry-after' => $response->header('Retry-After'),
                'content-type' => $response->header('Content-Type'),
            ],
        ];
    }
}
