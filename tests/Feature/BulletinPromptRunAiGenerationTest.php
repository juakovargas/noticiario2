<?php

namespace Tests\Feature;

use App\Models\AiProvider;
use App\Models\BulletinPromptRun;
use App\Models\BulletinType;
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
        $run = BulletinPromptRun::factory()->create(['generated_prompt' => 'Prompt text', 'status' => 'prompt_ready']);

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

        $run = BulletinPromptRun::factory()->create(['generated_prompt' => 'Prompt text', 'status' => 'prompt_ready']);

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

    public function test_generation_is_blocked_when_daily_limit_reached_without_external_call(): void
    {
        putenv('OPENAI_API_KEY=test-key');
        Http::fake(['*' => Http::response(['ok' => true], 200)]);

        $editor = $this->createUserWithPermissions(['editor.access']);
        $provider = AiProvider::query()->create([
            'name' => 'OpenAI', 'slug' => 'openai', 'provider_type' => 'openai', 'base_url' => 'https://api.openai.com/v1', 'api_key_env_name' => 'OPENAI_API_KEY', 'default_model' => 'gpt-4o-mini', 'is_active' => true, 'is_default' => true, 'timeout_seconds' => 60, 'daily_request_limit' => 1,
        ]);
        $run = BulletinPromptRun::factory()->create(['generated_prompt' => 'Prompt text', 'status' => 'prompt_ready']);

        $this->assertDatabaseCount('ai_request_logs', 0);
        $this->assertDatabaseHas('ai_providers', ['id' => $provider->id]);

        \App\Models\AiRequestLog::query()->create(['ai_provider_id' => $provider->id, 'status' => 'success']);

        $this->actingAs($editor)->post(route('editor.bulletin-prompt-runs.generate-ai-response', $run))->assertSessionHas('error');

        Http::assertNothingSent();
        $this->assertDatabaseHas('ai_request_logs', ['bulletin_prompt_run_id' => $run->id, 'limit_blocked' => true, 'error_code' => 'limit_reached']);
    }
}
