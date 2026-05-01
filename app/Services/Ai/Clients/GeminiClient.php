<?php

namespace App\Services\Ai\Clients;

use App\Models\AiProvider;
use App\Services\Ai\AiProviderRateLimiter;
use App\Services\Ai\Contracts\AiClient;
use App\Services\Ai\Data\AiResponseData;
use App\Services\Ai\Exceptions\AiProviderException;
use Illuminate\Support\Facades\Http;

class GeminiClient implements AiClient
{
    public function __construct(private readonly AiProviderRateLimiter $rateLimiter = new AiProviderRateLimiter()) {}

    public function generateText(AiProvider $provider, string $prompt, array $options = []): AiResponseData
    {
        $key = trim((string) env($provider->api_key_env_name ?? 'GEMINI_API_KEY'));
        if ($key === '') throw new AiProviderException('Environment key is not configured.');

        $availability = $this->rateLimiter->getAvailabilityContext($provider);
        if (($availability['status'] ?? 'available') !== 'available') {
            $retryAfter = (int) ($availability['retry_after_seconds'] ?? 1);
            $msg = ($availability['status'] ?? null) === 'min_delay_wait'
                ? 'The provider was not called because the minimum delay between requests is still active.'
                : 'The provider was not called because it is temporarily rate-limited until '.($availability['rate_limited_until'] ?? now()->toISOString()).'.';
            throw new AiProviderException($msg, 429, true, $retryAfter);
        }

        $model = $provider->default_model ?: 'gemini-2.0-flash';
        $url = rtrim((string)($provider->base_url ?: 'https://generativelanguage.googleapis.com/v1beta'), '/')."/models/{$model}:generateContent";
        $payload = ['contents' => [['parts' => [['text' => $prompt]]]]];
        if ($provider->supports_grounding || $provider->supportsCapability('google_search_grounding')) $payload['tools'] = [['google_search' => (object)[]]];

        $maxRetries = $provider->retry_on_rate_limit ? (int) ($provider->max_retries ?? 2) : 0;
        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            $this->rateLimiter->markRequestStarted($provider);
            $response = Http::timeout($provider->timeoutSecondsForRequest())->withQueryParameters(['key'=>$key])->post($url, $payload);
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
                $msg = $attempt >= $maxRetries
                    ? 'Gemini returned 429 Too Many Requests and retry attempts are exhausted.'
                    : 'Gemini returned 429 Too Many Requests. Retry available in '.$retryAfter.' seconds. Attempt '.($attempt + 1).' of '.$maxRetries.'.';
                throw new AiProviderException($msg, 429, true, $retryAfter);
            }

            throw new AiProviderException('Gemini request failed with status '.$response->status(), $response->status(), $response->status() >= 500);
        }

        throw new AiProviderException('AI request failed.');
    }
}
