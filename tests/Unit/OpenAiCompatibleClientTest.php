<?php

namespace Tests\Unit;

use App\Models\AiProvider;
use App\Services\Ai\Clients\OpenAiCompatibleClient;
use App\Services\Ai\Exceptions\AiProviderException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiCompatibleClientTest extends TestCase
{
    public function test_it_sends_expected_request_and_extracts_response_tokens(): void
    {
        putenv('OPENAI_API_KEY=test-key');
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'model' => 'gpt-4o-mini',
                'choices' => [['message' => ['content' => 'Hello world'], 'finish_reason' => 'stop']],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 15, 'total_tokens' => 25],
            ], 200),
        ]);

        $provider = new AiProvider([
            'provider_type' => 'openai',
            'base_url' => 'https://api.openai.com/v1',
            'api_key_env_name' => 'OPENAI_API_KEY',
            'default_model' => 'gpt-4o-mini',
            'timeout_seconds' => 30,
        ]);

        $response = (new OpenAiCompatibleClient())->generateText($provider, 'Prompt');

        $this->assertSame('Hello world', $response->text);
        $this->assertSame(10, $response->inputTokens);
        $this->assertSame(15, $response->outputTokens);
        $this->assertSame(25, $response->totalTokens);
    }

    public function test_failed_http_response_is_handled_without_leaking_api_key(): void
    {
        putenv('OPENAI_API_KEY=super-secret-key');

        Http::fake([
            '*' => Http::response(['error' => ['message' => 'Bad request']], 400),
        ]);

        $provider = new AiProvider([
            'provider_type' => 'openai',
            'base_url' => 'https://api.openai.com/v1',
            'api_key_env_name' => 'OPENAI_API_KEY',
            'default_model' => 'gpt-4o-mini',
            'timeout_seconds' => 30,
        ]);

        try {
            (new OpenAiCompatibleClient())->generateText($provider, 'Prompt');
            $this->fail('Expected exception was not thrown.');
        } catch (AiProviderException $exception) {
            $this->assertStringNotContainsString('super-secret-key', $exception->getMessage());
            $this->assertStringContainsString('AI request failed', $exception->getMessage());
        }
    }
}
