<?php

namespace Tests\Feature;

use App\Models\AiProvider;
use App\Models\BulletinPromptRun;
use App\Models\BulletinType;
use App\Services\Ai\Contracts\LaravelAiSdkGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class BulletinPromptRunAiGenerationTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    public function test_editor_can_generate_ai_response_and_log_is_created(): void
    {
        putenv('OPENAI_API_KEY=test-key');
        Http::fake([
            '*' => Http::response([
                'model' => 'gpt-4o-mini',
                'choices' => [['message' => ['content' => "TITLE: Demo\n\nINTRO: Intro\n\nNEWS ITEMS:\nHEADLINE: One\nSCRIPT: Body"], 'finish_reason' => 'stop']],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 20, 'total_tokens' => 30],
            ]),
        ]);

        $editor = $this->createUserWithPermissions(['editor.access']);
        $provider = AiProvider::query()->create([
            'name' => 'OpenAI', 'slug' => 'openai', 'provider_type' => 'openai', 'base_url' => 'https://api.openai.com/v1', 'api_key_env_name' => 'OPENAI_API_KEY', 'default_model' => 'gpt-4o-mini', 'is_active' => true, 'is_default' => true, 'timeout_seconds' => 60,
        ]);
        $type = BulletinType::factory()->create(['preferred_ai_provider_id' => $provider->id]);
        $run = BulletinPromptRun::factory()->create(['bulletin_type_id' => $type->id, 'generated_prompt' => 'Prompt text', 'status' => 'prompt_ready']);

        $this->actingAs($editor)->post(route('editor.bulletin-prompt-runs.generate-ai-response', $run), ['ai_provider_id' => $provider->id])->assertRedirect();

        $this->assertNotNull($run->fresh()->ai_response_text);
        $this->assertNotNull($run->fresh()->parsed_response);
        $this->assertNotNull($run->fresh()->response_received_at);
        $this->assertDatabaseHas('ai_request_logs', ['bulletin_prompt_run_id' => $run->id, 'status' => 'success']);
    }

    public function test_archived_or_missing_prompt_runs_cannot_generate_ai_response(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);
        $archived = BulletinPromptRun::factory()->create(['status' => 'archived', 'generated_prompt' => 'Prompt']);
        $missingPrompt = BulletinPromptRun::factory()->create(['generated_prompt' => null]);

        $this->actingAs($editor)->post(route('editor.bulletin-prompt-runs.generate-ai-response', $archived))->assertSessionHas('error');
        $this->actingAs($editor)->post(route('editor.bulletin-prompt-runs.generate-ai-response', $missingPrompt))->assertSessionHas('error');
    }

    public function test_missing_env_key_blocks_api_providers_but_ollama_works_without_key(): void
    {
        Http::fake(['*' => Http::response(['response' => 'TITLE: Demo'], 200)]);

        $editor = $this->createUserWithPermissions(['editor.access']);
        $apiProvider = AiProvider::query()->create([
            'name' => 'OpenAI', 'slug' => 'openai', 'provider_type' => 'openai', 'base_url' => 'https://api.openai.com/v1', 'api_key_env_name' => 'MISSING_KEY', 'default_model' => 'gpt-4o-mini', 'is_active' => true, 'is_default' => true, 'timeout_seconds' => 60,
        ]);
        $ollama = AiProvider::query()->create([
            'name' => 'Ollama', 'slug' => 'ollama', 'provider_type' => 'ollama', 'base_url' => 'http://localhost:11434', 'default_model' => 'llama3.1:8b', 'is_active' => true, 'timeout_seconds' => 60,
        ]);
        $run = BulletinPromptRun::factory()->create(['generated_prompt' => 'Prompt', 'status' => 'prompt_ready']);

        $this->actingAs($editor)->post(route('editor.bulletin-prompt-runs.generate-ai-response', $run), ['ai_provider_id' => $apiProvider->id])->assertSessionHas('error');
        $this->actingAs($editor)->post(route('editor.bulletin-prompt-runs.generate-ai-response', $run), ['ai_provider_id' => $ollama->id])->assertRedirect();
    }

    public function test_failed_generation_creates_failed_log_and_viewer_cannot_generate(): void
    {
        putenv('OPENAI_API_KEY=test-key');
        Http::fake(['*' => Http::response(['error' => ['message' => 'bad']], 500)]);

        $editor = $this->createUserWithPermissions(['editor.access']);
        $viewer = $this->createUserWithPermissions(['viewer.access']);

        AiProvider::query()->create([
            'name' => 'OpenAI', 'slug' => 'openai', 'provider_type' => 'openai', 'base_url' => 'https://api.openai.com/v1', 'api_key_env_name' => 'OPENAI_API_KEY', 'default_model' => 'gpt-4o-mini', 'is_active' => true, 'is_default' => true, 'timeout_seconds' => 60,
        ]);

        $type = BulletinType::factory()->create(['preferred_ai_provider_id' => AiProvider::query()->where('slug', 'openai')->value('id')]);
        $run = BulletinPromptRun::factory()->create(['bulletin_type_id' => $type->id, 'generated_prompt' => 'Prompt text', 'status' => 'prompt_ready']);

        $this->actingAs($editor)->post(route('editor.bulletin-prompt-runs.generate-ai-response', $run))->assertSessionHas('error');
        $this->assertDatabaseHas('ai_request_logs', ['bulletin_prompt_run_id' => $run->id, 'status' => 'failed']);

        $this->actingAs($viewer)->post(route('editor.bulletin-prompt-runs.generate-ai-response', $run))->assertForbidden();
    }

    public function test_manual_save_ai_response_still_works(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);
        $type = BulletinType::factory()->create();
        $run = BulletinPromptRun::factory()->create(['bulletin_type_id' => $type->id]);

        $this->actingAs($editor)->post(route('editor.bulletin-prompt-runs.save-response', $run), ['response_text' => 'TITLE: Manual'])->assertRedirect();
        $this->assertSame('TITLE: Manual', $run->fresh()->ai_response_text);
    }

    public function test_generation_without_bulletin_provider_returns_clear_configuration_error(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);
        $type = BulletinType::factory()->create(['preferred_ai_provider_id' => null]);
        $run = BulletinPromptRun::factory()->create(['bulletin_type_id' => $type->id, 'generated_prompt' => 'Prompt', 'status' => 'prompt_ready']);

        $this->actingAs($editor)
            ->post(route('editor.bulletin-prompt-runs.generate-ai-response', $run))
            ->assertSessionHas('error', 'No AI provider configured for this bulletin.');
    }

    public function test_generation_is_blocked_when_daily_limit_reached_without_external_call(): void
    {
        putenv('OPENAI_API_KEY=test-key');
        Http::fake(['*' => Http::response(['ok' => true], 200)]);

        $editor = $this->createUserWithPermissions(['editor.access']);
        $provider = AiProvider::query()->create([
            'name' => 'OpenAI', 'slug' => 'openai', 'provider_type' => 'openai', 'base_url' => 'https://api.openai.com/v1', 'api_key_env_name' => 'OPENAI_API_KEY', 'default_model' => 'gpt-4o-mini', 'is_active' => true, 'is_default' => true, 'timeout_seconds' => 60, 'daily_request_limit' => 1,
        ]);
        $type = BulletinType::factory()->create(['preferred_ai_provider_id' => $provider->id]);
        $run = BulletinPromptRun::factory()->create(['bulletin_type_id' => $type->id, 'generated_prompt' => 'Prompt text', 'status' => 'prompt_ready']);

        $this->assertDatabaseCount('ai_request_logs', 0);
        $this->assertDatabaseHas('ai_providers', ['id' => $provider->id]);

        \App\Models\AiRequestLog::query()->create(['ai_provider_id' => $provider->id, 'status' => 'success']);

        $this->actingAs($editor)->post(route('editor.bulletin-prompt-runs.generate-ai-response', $run))->assertSessionHas('error');

        Http::assertNothingSent();
        $this->assertDatabaseHas('ai_request_logs', ['bulletin_prompt_run_id' => $run->id, 'limit_blocked' => true, 'error_code' => 'limit_reached']);
    }

    public function test_laravel_ai_driver_can_generate_and_still_logs_request(): void
    {
        app()->instance(LaravelAiSdkGateway::class, new class implements LaravelAiSdkGateway
        {
            public function generateText(string $providerAlias, string $model, string $prompt, array $options = []): array
            {
                return [
                    'text' => "TITLE: SDK Demo\n\nINTRO: Intro from sdk",
                    'model' => $model,
                    'input_tokens' => 9,
                    'output_tokens' => 12,
                    'total_tokens' => 21,
                    'finish_reason' => 'stop',
                ];
            }
        });

        $editor = $this->createUserWithPermissions(['editor.access']);
        $provider = AiProvider::query()->create([
            'name' => 'OpenAI SDK',
            'slug' => 'openai-sdk',
            'provider_type' => 'openai',
            'client_driver' => 'laravel_ai',
            'base_url' => 'https://api.openai.com/v1',
            'api_key_env_name' => 'OPENAI_API_KEY',
            'default_model' => 'gpt-4.1-mini',
            'is_active' => true,
            'is_default' => true,
            'timeout_seconds' => 60,
        ]);
        $run = BulletinPromptRun::factory()->create(['generated_prompt' => 'Prompt text', 'status' => 'prompt_ready']);

        $this->actingAs($editor)
            ->post(route('editor.bulletin-prompt-runs.generate-ai-response', $run), ['ai_provider_id' => $provider->id])
            ->assertRedirect();

        $this->assertDatabaseHas('ai_request_logs', [
            'bulletin_prompt_run_id' => $run->id,
            'status' => 'success',
            'model' => 'gpt-4.1-mini',
        ]);
        $this->assertNotNull($run->fresh()->ai_response_text);
    }

    public function test_gemini_grounded_resolves_from_bulletin_preferred_provider_and_env_key(): void
    {
        putenv('GEMINI_API_KEY=test-gemini-key');
        config(['services.gemini.key' => 'test-gemini-key']);

        Http::fake(function ($request) {
            $this->assertSame('test-gemini-key', $request->header('x-goog-api-key')[0] ?? null);
            $this->assertStringContainsString('/models/gemini-3-flash-preview:generateContent', $request->url());

            return Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'Guion final listo para locucion.']]],
                    'finishReason' => 'STOP',
                    'groundingMetadata' => ['searchEntryPoint' => ['renderedContent' => '']],
                ]],
                'usageMetadata' => ['promptTokenCount' => 10, 'candidatesTokenCount' => 8, 'totalTokenCount' => 18],
            ]);
        });

        $editor = $this->createUserWithPermissions(['editor.access']);
        $provider = AiProvider::query()->create([
            'name' => 'Gemini Grounded',
            'slug' => 'gemini-grounded',
            'provider_type' => 'gemini',
            'client_driver' => 'custom',
            'provider_category' => 'grounded_text',
            'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
            'api_key_env_name' => 'GEMINI_API_KEY',
            'default_model' => 'gemini-3-flash-preview',
            'is_active' => true,
            'is_default' => false,
            'supports_grounding' => true,
            'capabilities' => ['script_generation', 'news_grounding', 'google_search_grounding'],
            'timeout_seconds' => 60,
            'max_tokens' => 1000,
        ]);
        $type = BulletinType::factory()->create([
            'preferred_ai_provider_id' => $provider->id,
            'metadata' => ['requires_current_news' => true, 'requires_grounded_news' => true],
        ]);
        $run = BulletinPromptRun::factory()->create(['bulletin_type_id' => $type->id, 'generated_prompt' => 'Prompt actual de Espana', 'status' => 'prompt_ready']);

        $this->actingAs($editor)
            ->post(route('editor.bulletin-prompt-runs.generate-ai-response', $run))
            ->assertRedirect();

        $this->assertSame('Guion final listo para locucion.', $run->fresh()->ai_response_text);
        $this->assertDatabaseHas('ai_request_logs', [
            'bulletin_prompt_run_id' => $run->id,
            'ai_provider_id' => $provider->id,
            'model' => 'gemini-3-flash-preview',
            'status' => 'success',
        ]);
    }
}
