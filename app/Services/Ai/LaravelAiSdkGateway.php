<?php

namespace App\Services\Ai;

use App\Services\Ai\Contracts\LaravelAiSdkGateway as LaravelAiSdkGatewayContract;
use App\Services\Ai\Exceptions\AiProviderException;
use Illuminate\Support\Arr;

class LaravelAiSdkGateway implements LaravelAiSdkGatewayContract
{
    public function generateText(string $providerAlias, string $model, string $prompt, array $options = []): array
    {
        if (! class_exists(\Laravel\Ai\Facades\Ai::class)) {
            throw new AiProviderException('Laravel AI SDK is not installed in this environment.');
        }

        try {
            $builder = \Laravel\Ai\Facades\Ai::provider($providerAlias)->model($model);

            if (isset($options['temperature'])) {
                $builder = $builder->with(['temperature' => (float) $options['temperature']]);
            }

            if (isset($options['max_tokens'])) {
                $builder = $builder->with(['max_tokens' => (int) $options['max_tokens']]);
            }

            $result = $builder->text($prompt);
        } catch (\Throwable $e) {
            throw new AiProviderException('Laravel AI SDK request failed.');
        }

        return [
            'text' => (string) (method_exists($result, 'text') ? $result->text() : (data_get($result, 'text') ?? '')),
            'raw' => method_exists($result, 'toArray') ? $result->toArray() : (array) $result,
            'model' => method_exists($result, 'model') ? $result->model() : Arr::get((array) $result, 'model', $model),
            'input_tokens' => method_exists($result, 'inputTokens') ? $result->inputTokens() : Arr::get((array) $result, 'usage.input_tokens'),
            'output_tokens' => method_exists($result, 'outputTokens') ? $result->outputTokens() : Arr::get((array) $result, 'usage.output_tokens'),
            'total_tokens' => method_exists($result, 'totalTokens') ? $result->totalTokens() : Arr::get((array) $result, 'usage.total_tokens'),
            'finish_reason' => method_exists($result, 'finishReason') ? $result->finishReason() : Arr::get((array) $result, 'finish_reason'),
        ];
    }
}
