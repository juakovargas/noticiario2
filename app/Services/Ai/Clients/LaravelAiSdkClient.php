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
        if (! in_array($provider->provider_type, ['openai', 'openrouter', 'groq', 'gemini', 'google_gemini'], true)) {
            throw new AiProviderException(sprintf('Laravel AI SDK client does not support provider type: %s', $provider->provider_type));
        }

        $model = (string) ($options['model'] ?? $provider->default_model ?? '');
        if ($model === '') {
            throw new AiProviderException('Model is required for AI generation.');
        }

        $startedAt = microtime(true);
        try {
            $result = $this->gateway->generateText(
                providerAlias: $this->providerAlias($provider->provider_type),
                model: $model,
                prompt: $prompt,
                options: [
                    'temperature' => $options['temperature'] ?? $provider->temperature,
                    'max_tokens' => $options['max_tokens'] ?? $provider->max_tokens,
                ],
            );
        } catch (\Throwable $e) {
            throw new AiProviderException(
                message: $this->buildSdkSafeMessage($provider, $e),
                requestWasSent: false,
                errorCode: $this->resolveFailureCode($provider, $e),
                previous: $e,
            );
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

    private function buildSdkSafeMessage(AiProvider $provider, \Throwable $error): string
    {
        if (! class_exists(\Laravel\Ai\Facades\Ai::class)) {
            return 'Laravel AI SDK is not installed.';
        }

        if (($provider->api_key_env_name ?? '') === '' || ! (bool) env((string) $provider->api_key_env_name)) {
            return 'Missing GEMINI_API_KEY for Laravel AI SDK.';
        }

        return 'Laravel AI SDK request failed: '.$error->getMessage();
    }

    private function resolveFailureCode(AiProvider $provider, \Throwable $error): string
    {
        if (! class_exists(\Laravel\Ai\Facades\Ai::class)) {
            return 'sdk_package_missing';
        }

        if (($provider->api_key_env_name ?? '') === '' || ! (bool) env((string) $provider->api_key_env_name)) {
            return 'sdk_key_missing';
        }

        return 'sdk_call_failed';
    }

    private function providerAlias(string $providerType): string
    {
        return match ($providerType) {
            'google_gemini' => 'gemini',
            default => $providerType,
        };
    }
}

