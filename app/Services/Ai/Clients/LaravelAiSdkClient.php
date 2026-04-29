<?php

namespace App\Services\Ai\Clients;

use App\Models\AiProvider;
use App\Services\Ai\Contracts\AiClient;
use App\Services\Ai\Contracts\LaravelAiSdkGateway;
use App\Services\Ai\Data\AiResponseData;
use App\Services\Ai\Exceptions\AiProviderException;

class LaravelAiSdkClient implements AiClient
{
    public function __construct(private readonly LaravelAiSdkGateway $gateway)
    {
    }

    public function generateText(AiProvider $provider, string $prompt, array $options = []): AiResponseData
    {
        if (! in_array($provider->provider_type, ['openai', 'openrouter'], true)) {
            throw new AiProviderException(sprintf('Laravel AI SDK client does not support provider type: %s', $provider->provider_type));
        }

        $model = (string) ($options['model'] ?? $provider->default_model ?? '');
        if ($model === '') {
            throw new AiProviderException('Model is required for AI generation.');
        }

        $startedAt = microtime(true);
        try {
            $result = $this->gateway->generateText(
                providerAlias: $provider->provider_type,
                model: $model,
                prompt: $prompt,
                options: [
                    'temperature' => $options['temperature'] ?? $provider->temperature,
                    'max_tokens' => $options['max_tokens'] ?? $provider->max_tokens,
                ],
            );
        } catch (\Throwable $e) {
            throw new AiProviderException('Laravel AI SDK request failed.');
        }
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        return new AiResponseData(
            text: trim((string) ($result['text'] ?? '')),
            rawResponse: is_array($result['raw'] ?? null) ? $result['raw'] : null,
            provider: $provider->provider_type,
            model: (string) ($result['model'] ?? $model),
            inputTokens: isset($result['input_tokens']) ? (int) $result['input_tokens'] : null,
            outputTokens: isset($result['output_tokens']) ? (int) $result['output_tokens'] : null,
            totalTokens: isset($result['total_tokens']) ? (int) $result['total_tokens'] : null,
            finishReason: isset($result['finish_reason']) ? (string) $result['finish_reason'] : null,
            durationMs: $durationMs,
        );
    }
}
