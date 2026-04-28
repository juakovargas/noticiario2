<?php

namespace App\Services\Ai;

use App\Data\AiResponse;
use App\Models\AiProvider;
use App\Services\Ai\Clients\OllamaClient;
use App\Services\Ai\Clients\OpenAiCompatibleClient;
use App\Services\Ai\Contracts\AiClient;
use App\Services\Ai\Data\AiResponseData;
use App\Services\Ai\Exceptions\AiProviderException;
use RuntimeException;

class AiClientManager
{
    public function resolve(AiProvider $provider): AiClientInterface
    {
        if ($provider->provider_type === 'mock') {
            return new MockAiClient();
        }

        throw new RuntimeException(sprintf('Provider not implemented yet: %s', $provider->provider_type));
    }

    public function generateText(AiProvider $provider, string $prompt, array $options = []): AiResponseData
    {
        return $this->resolveTextClient($provider)->generateText($provider, $prompt, $options);
    }

    private function resolveTextClient(AiProvider $provider): AiClient
    {
        return match ($provider->provider_type) {
            'openai', 'openrouter', 'custom_openai_compatible' => new OpenAiCompatibleClient(),
            'ollama' => new OllamaClient(),
            'mock' => new class implements AiClient {
                public function generateText(AiProvider $provider, string $prompt, array $options = []): AiResponseData
                {
                    return new AiResponseData(
                        text: 'Mock provider response for testing.',
                        rawResponse: ['mock' => true],
                        provider: 'mock',
                        model: 'mock-model',
                        inputTokens: 10,
                        outputTokens: 10,
                        totalTokens: 20,
                        finishReason: 'stop',
                        durationMs: 1,
                    );
                }
            },
            default => throw new AiProviderException(sprintf('Provider type is not supported for text generation: %s', $provider->provider_type)),
        };
    }
}
