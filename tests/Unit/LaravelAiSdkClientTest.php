<?php

namespace Tests\Unit;

use App\Models\AiProvider;
use App\Services\Ai\Clients\LaravelAiSdkClient;
use App\Services\Ai\Contracts\LaravelAiSdkGateway;
use App\Services\Ai\Exceptions\AiProviderException;
use Tests\TestCase;

class LaravelAiSdkClientTest extends TestCase
{
    public function test_it_can_be_instantiated_and_returns_ai_response_data(): void
    {
        $gateway = new class implements LaravelAiSdkGateway
        {
            public function generateText(string $providerAlias, string $model, string $prompt, array $options = []): array
            {
                return [
                    'text' => 'Adapter response',
                    'raw' => ['ok' => true],
                    'model' => $model,
                    'input_tokens' => 11,
                    'output_tokens' => 22,
                    'total_tokens' => 33,
                    'finish_reason' => 'stop',
                ];
            }
        };

        $client = new LaravelAiSdkClient($gateway);
        $provider = new AiProvider([
            'provider_type' => 'openai',
            'default_model' => 'gpt-test',
        ]);

        $response = $client->generateText($provider, 'Prompt');

        $this->assertSame('Adapter response', $response->text);
        $this->assertSame(33, $response->totalTokens);
        $this->assertSame('gpt-test', $response->model);
    }

    public function test_it_returns_safe_error_for_unsupported_provider_type(): void
    {
        $gateway = new class implements LaravelAiSdkGateway
        {
            public function generateText(string $providerAlias, string $model, string $prompt, array $options = []): array
            {
                return [];
            }
        };

        $this->expectException(AiProviderException::class);
        $this->expectExceptionMessage('does not support provider type');

        (new LaravelAiSdkClient($gateway))->generateText(new AiProvider([
            'provider_type' => 'ollama',
            'default_model' => 'llama3.1:8b',
        ]), 'Prompt');
    }

    public function test_it_does_not_expose_raw_exception_message_from_gateway(): void
    {
        $gateway = new class implements LaravelAiSdkGateway
        {
            public function generateText(string $providerAlias, string $model, string $prompt, array $options = []): array
            {
                throw new AiProviderException('OPENAI_API_KEY=secret');
            }
        };

        $this->expectException(AiProviderException::class);
        $this->expectExceptionMessage('Laravel AI SDK request failed.');

        (new LaravelAiSdkClient($gateway))->generateText(new AiProvider([
            'provider_type' => 'openai',
            'default_model' => 'gpt-test',
        ]), 'Prompt');
    }
}
