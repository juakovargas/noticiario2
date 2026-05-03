<?php

namespace Tests\Feature;

use App\Models\AiProvider;
use App\Models\BulletinPromptRun;
use App\Models\BulletinType;
use App\Models\EditorialSchedule;
use App\Models\EditorialScheduleRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class EditorialScheduleRunProductFlowTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    public function test_schedule_run_show_exposes_ai_and_script_next_action_after_prompt_generation(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);
        $provider = AiProvider::factory()->create(['name' => 'Gemini Grounded', 'default_model' => 'gemini-3-flash-preview']);
        $bulletin = BulletinType::factory()->create(['preferred_ai_provider_id' => $provider->id]);
        $schedule = EditorialSchedule::factory()->create([
            'bulletin_type_id' => $bulletin->id,
            'auto_generate_ai_response' => false,
            'auto_run_pipeline' => false,
        ]);
        $run = EditorialScheduleRun::factory()->create([
            'editorial_schedule_id' => $schedule->id,
            'status' => 'prompt_generated',
            'generated_prompt' => 'Prompt listo',
        ]);
        $promptRun = BulletinPromptRun::factory()->create([
            'bulletin_type_id' => $bulletin->id,
            'editorial_schedule_id' => $schedule->id,
            'editorial_schedule_run_id' => $run->id,
            'generated_prompt' => 'Prompt listo',
            'status' => 'prompt_ready',
        ]);
        $run->update(['bulletin_prompt_run_id' => $promptRun->id]);

        $this->actingAs($editor)
            ->get(route('editor.editorial-schedule-runs.show', $run))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Editor/EditorialScheduleRuns/Show')
                ->where('run.prompt_generated', true)
                ->where('run.ai_response_received', false)
                ->where('run.urls.run_pipeline', route('editor.bulletin-prompt-runs.run-pipeline', $promptRun))
                ->where('run.provider.name', 'Gemini Grounded')
                ->where('run.provider.model', 'gemini-3-flash-preview')
            );
    }
}
