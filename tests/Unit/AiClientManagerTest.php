<?php

namespace Tests\Unit;

use App\Models\AiProvider;
use App\Services\Ai\AiClientManager;
use App\Services\Ai\Contracts\LaravelAiSdkGateway;
use App\Services\Ai\Exceptions\AiProviderException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiClientManagerTest extends TestCase
{
    public function test_custom_driver_uses_existing_custom_client_behavior(): void
    {
        putenv('OPENAI_API_KEY=test-key');
        Http::fake([
            '*' => Http::response([
                'model' => 'gpt-4o-mini',
                'choices' => [['message' => ['content' => 'Custom response'], 'finish_reason' => 'stop']],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 10, 'total_tokens' => 20],
            ]),
        ]);

        $manager = app(AiClientManager::class);
        $provider = new AiProvider([
            'provider_type' => 'openai',
            'client_driver' => 'custom',
            'base_url' => 'https://api.openai.com/v1',
            'api_key_env_name' => 'OPENAI_API_KEY',
            'default_model' => 'gpt-4o-mini',
            'timeout_seconds' => 60,
        ]);

        $response = $manager->generateText($provider, 'Prompt');

        $this->assertSame('Custom response', $response->text);
    }

    public function test_laravel_ai_driver_uses_laravel_ai_sdk_client(): void
    {
        app()->instance(LaravelAiSdkGateway::class, new class implements LaravelAiSdkGateway
        {
            public function generateText(string $providerAlias, string $model, string $prompt, array $options = []): array
            {
                return ['text' => 'SDK response', 'model' => $model];
            }
        });

        $manager = app(AiClientManager::class);
        $provider = new AiProvider([
            'provider_type' => 'openai',
            'client_driver' => 'laravel_ai',
            'default_model' => 'gpt-4.1-mini',
        ]);

        $response = $manager->generateText($provider, 'Prompt');

        $this->assertSame('SDK response', $response->text);
        $this->assertSame('gpt-4.1-mini', $response->model);
    }

    public function test_laravel_ai_driver_returns_safe_error_for_unsupported_provider_type(): void
    {
        app()->instance(LaravelAiSdkGateway::class, new class implements LaravelAiSdkGateway
        {
            public function generateText(string $providerAlias, string $model, string $prompt, array $options = []): array
            {
                return [];
            }
        });

        $this->expectException(AiProviderException::class);

        app(AiClientManager::class)->generateText(new AiProvider([
            'provider_type' => 'custom_openai_compatible',
            'client_driver' => 'laravel_ai',
            'default_model' => 'gpt-4.1-mini',
        ]), 'Prompt');
    }
}
