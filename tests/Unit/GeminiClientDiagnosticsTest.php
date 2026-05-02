<?php

namespace Tests\Unit;

use App\Models\AiProvider;
use App\Services\Ai\Clients\GeminiClient;
use App\Services\Ai\Exceptions\AiProviderException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiClientDiagnosticsTest extends TestCase
{
    public function test_gemini_uses_x_goog_api_key_header(): void
    {
        putenv('GEMINI_API_KEY=test-key');
        Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'OK']]]]], 'usageMetadata' => []], 200)]);
        $provider = new AiProvider(['provider_type' => 'gemini', 'api_key_env_name' => 'GEMINI_API_KEY', 'default_model' => 'gemini-2.0-flash', 'timeout_seconds' => 30, 'base_url' => 'https://generativelanguage.googleapis.com/v1beta']);

        (new GeminiClient())->generateText($provider, 'Responde solo OK.');

        Http::assertSent(function ($request) {
            return $request->hasHeader('x-goog-api-key', 'test-key') && ! $request->hasHeader('Authorization');
        });
    }

    public function test_missing_api_key_throws_without_request(): void
    {
        putenv('GEMINI_API_KEY');
        Http::fake();
        $provider = new AiProvider(['provider_type' => 'gemini', 'api_key_env_name' => 'GEMINI_API_KEY', 'default_model' => 'gemini-2.0-flash', 'timeout_seconds' => 30]);

        try {
            (new GeminiClient())->generateText($provider, 'Responde solo OK.');
            $this->fail('Expected exception');
        } catch (AiProviderException $e) {
            $this->assertSame('api_key_missing', $e->errorCode);
            $this->assertFalse($e->requestWasSent);
        }
        Http::assertNothingSent();
    }
}
