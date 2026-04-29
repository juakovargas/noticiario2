<?php

namespace App\Services\Ai\Clients;

use App\Models\AiProvider;
use App\Services\Ai\Contracts\AiClient;
use App\Services\Ai\Data\AiResponseData;
use App\Services\Ai\Exceptions\AiProviderException;
use App\Services\Ai\Support\AiRequestOptionsNormalizer;
use Illuminate\Support\Facades\Http;

class OpenAiCompatibleClient implements AiClient
{
    public function generateText(AiProvider $provider, string $prompt, array $options = []): AiResponseData
    {
        $apiKey = $this->resolveApiKey($provider);
        $baseUrl = rtrim((string) ($provider->base_url ?: ''), '/');

        if ($baseUrl === '') {
            throw new AiProviderException('Base URL is required for AI provider.');
        }

        $model = (string) ($options['model'] ?? $provider->default_model ?? '');
        if ($model === '') {
            throw new AiProviderException('Model is required for AI generation.');
        }

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a professional editorial assistant.'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ];
        $requestOptions = AiRequestOptionsNormalizer::normalize([
            'temperature' => $options['temperature'] ?? $provider->temperatureForRequest(),
            'max_tokens' => $options['max_tokens'] ?? $provider->maxTokensForRequest(),
            'top_p' => $options['top_p'] ?? null,
            'frequency_penalty' => $options['frequency_penalty'] ?? null,
            'presence_penalty' => $options['presence_penalty'] ?? null,
            'stream' => $options['stream'] ?? null,
        ]);
        $payload = [...$payload, ...$requestOptions];

        $headers = [
            'Authorization' => 'Bearer '.$apiKey,
            'Content-Type' => 'application/json',
        ];

        if ($provider->provider_type === 'openrouter') {
            $headers['HTTP-Referer'] = config('app.url');
            $headers['X-Title'] = 'Noticiario';
        }

        $startedAt = microtime(true);
        $response = Http::timeout($provider->timeoutSecondsForRequest())
            ->withHeaders($headers)
            ->post($baseUrl.'/chat/completions', $payload);
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        if (! $response->successful()) {
            $errorPayload = $response->json();
            $errorText = data_get($errorPayload, 'error.message') ?? $response->body();
            throw new AiProviderException(sprintf('AI request failed with status %s: %s', $response->status(), str($errorText)->limit(300)));
        }

        $data = $response->json();
        $text = trim((string) data_get($data, 'choices.0.message.content', ''));

        return new AiResponseData(
            text: $text,
            rawResponse: is_array($data) ? $data : null,
            provider: $provider->provider_type,
            model: (string) (data_get($data, 'model') ?: $model),
            inputTokens: data_get($data, 'usage.prompt_tokens'),
            outputTokens: data_get($data, 'usage.completion_tokens'),
            totalTokens: data_get($data, 'usage.total_tokens'),
            finishReason: data_get($data, 'choices.0.finish_reason'),
            durationMs: $durationMs,
        );
    }

    private function resolveApiKey(AiProvider $provider): string
    {
        $envName = trim((string) $provider->api_key_env_name);

        if ($envName === '') {
            throw new AiProviderException('Environment key is not configured.');
        }

        $key = env($envName);
        if (! filled($key)) {
            throw new AiProviderException('Environment key is not configured.');
        }

        return (string) $key;
    }
}
