<?php

namespace Tests\Feature;

use App\Models\BulletinPromptRun;
use App\Models\BulletinType;
use App\Models\PromptProfile;
use App\Services\Pipelines\BulletinPromptRunPipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroundedProviderPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_news_run_fails_without_grounded_provider_and_no_groq_fallback(): void
    {
        $type = BulletinType::factory()->create([
            'metadata' => ['requires_grounded_news' => true],
        ]);

        $run = BulletinPromptRun::factory()->create([
            'bulletin_type_id' => $type->id,
            'prompt_profile_id' => PromptProfile::factory()->create()->id,
            'generated_prompt' => 'test prompt',
        ]);

        $summary = app(BulletinPromptRunPipeline::class)->run($run, null, [
            'allow_ai_call' => true,
        ]);

        $this->assertFalse($summary['success']);
        $this->assertSame('ai_provider', $summary['failed_step']);
        $this->assertSame('No grounded news provider is configured for this informativo.', $summary['message']);
    }
}
