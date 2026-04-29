<?php

namespace Tests\Feature;

use App\Models\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TestAiProviderCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_does_not_call_external_api(): void
    {
        AiProvider::query()->create(['name'=>'Groq','slug'=>'groq','provider_type'=>'groq','base_url'=>'https://api.groq.com/openai/v1','api_key_env_name'=>'GROQ_API_KEY','default_model'=>'llama-3.3-70b-versatile','is_active'=>true,'timeout_seconds'=>60]);
        putenv('GROQ_API_KEY=test-key');
        Http::fake();

        $this->artisan('noticiario:test-ai-provider', ['provider' => 'groq', '--dry-run' => true])->assertSuccessful();
        Http::assertNothingSent();
    }
}
