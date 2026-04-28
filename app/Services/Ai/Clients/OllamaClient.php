<?php

namespace App\Services\Ai\Clients;

use App\Models\AiProvider;
use App\Services\Ai\Contracts\AiClient;
use App\Services\Ai\Data\AiResponseData;
use App\Services\Ai\Exceptions\AiProviderException;
use Illuminate\Support\Facades\Http;

class OllamaClient implements AiClient
{
    public function generateText(AiProvider $provider, string $prompt, array $options = []): AiResponseData
    {
        $baseUrl = rtrim((string) ($provider->base_url ?: ''), '/');
        if ($baseUrl === '') {
            throw new AiProviderException('Base URL is required for AI provider.');
        }

        $model = (string) ($options['model'] ?? $provider->default_model ?? '');
        if ($model === '') {
            throw new AiProviderException('Model is required for AI generation.');
        }

        $startedAt = microtime(true);

        $response = Http::timeout((int) ($provider->timeout_seconds ?: 60))
            ->post($baseUrl.'/api/generate', [
                'model' => $model,
                'prompt' => $prompt,
                'stream' => false,
                'options' => array_filter([
                    'temperature' => $options['temperature'] ?? $provider->temperature,
                    'num_predict' => $options['max_tokens'] ?? $provider->max_tokens,
                ], fn ($value) => $value !== null),
            ]);

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        if (! $response->successful()) {
            throw new AiProviderException(sprintf('AI request failed with status %s: %s', $response->status(), str($response->body())->limit(300)));
        }

        $data = $response->json();

        return new AiResponseData(
            text: trim((string) data_get($data, 'response', '')),
            rawResponse: is_array($data) ? $data : null,
            provider: $provider->provider_type,
            model: $model,
            inputTokens: data_get($data, 'prompt_eval_count'),
            outputTokens: data_get($data, 'eval_count'),
            totalTokens: (data_get($data, 'prompt_eval_count') ?? 0) + (data_get($data, 'eval_count') ?? 0),
            finishReason: data_get($data, 'done_reason'),
            durationMs: $durationMs,
        );
    }
}
