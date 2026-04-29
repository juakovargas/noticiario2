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
            'temperature' => '0.40',
            'max_tokens' => '3000',
        ]);

        $response = (new OpenAiCompatibleClient())->generateText($provider, 'Prompt');
        Http::assertSent(function ($request) {
            $data = $request->data();
            return is_float($data['temperature']) && is_int($data['max_tokens']);
        });

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
    public function test_groq_uses_openai_compatible_endpoint(): void
    {
        putenv('GROQ_API_KEY=test-key');
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'model' => 'llama-3.3-70b-versatile',
                'choices' => [['message' => ['content' => 'OK'], 'finish_reason' => 'stop']],
                'usage' => ['prompt_tokens' => 1, 'completion_tokens' => 1, 'total_tokens' => 2],
            ], 200),
        ]);

        $provider = new AiProvider([
            'provider_type' => 'groq',
            'base_url' => 'https://api.groq.com/openai/v1',
            'api_key_env_name' => 'GROQ_API_KEY',
            'default_model' => 'llama-3.3-70b-versatile',
            'timeout_seconds' => 30,
        ]);

        $response = (new OpenAiCompatibleClient())->generateText($provider, 'Return exactly: OK');

        $this->assertSame('OK', $response->text);
    }

    public function test_string_numeric_values_are_normalized_for_payload(): void
    {
        putenv('GROQ_API_KEY=test-key');
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => 'OK']]]], 200)]);

        $provider = new AiProvider([
            'provider_type' => 'groq',
            'base_url' => 'https://api.groq.com/openai/v1',
            'api_key_env_name' => 'GROQ_API_KEY',
            'default_model' => 'llama-3.3-70b-versatile',
            'timeout_seconds' => '60',
            'temperature' => '0.40',
            'max_tokens' => '3000',
        ]);

        (new OpenAiCompatibleClient())->generateText($provider, 'Prompt', ['temperature' => '0.55', 'max_tokens' => '2000']);
        Http::assertSent(fn ($request) => is_float($request->data()['temperature']) && is_int($request->data()['max_tokens']));
    }

}
