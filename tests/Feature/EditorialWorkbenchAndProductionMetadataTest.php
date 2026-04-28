<?php

namespace Tests\Feature;

use App\Models\BulletinPromptRun;
use App\Models\BulletinType;
use App\Models\Script;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class EditorialWorkbenchAndProductionMetadataTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    #[Test]
    public function editor_can_access_workbench_and_viewer_cannot(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);
        $viewer = $this->createUserWithPermissions(['viewer.access']);

        $this->actingAs($editor)->get(route('editor.workbench'))->assertOk();
        $this->actingAs($viewer)->get(route('editor.workbench'))->assertForbidden();
    }

    #[Test]
    public function workbench_shows_operational_lists_and_excludes_archived_records(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);
        $type = BulletinType::factory()->create();

        BulletinPromptRun::factory()->create(['bulletin_type_id' => $type->id, 'status' => 'prompt_ready', 'title' => 'Waiting response']);
        BulletinPromptRun::factory()->create(['bulletin_type_id' => $type->id, 'status' => 'response_received', 'script_id' => null, 'title' => 'Ready script']);
        BulletinPromptRun::factory()->create(['bulletin_type_id' => $type->id, 'status' => 'archived', 'title' => 'Archived run']);

        Script::factory()->create(['review_status' => 'pending', 'title' => 'Needs review']);
        Script::factory()->create(['status' => 'archived', 'review_status' => 'pending', 'title' => 'Archived script']);

        $this->actingAs($editor)
            ->get(route('editor.workbench'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Editor/Workbench/Index')
                ->where('cards.prompt_runs_waiting_for_response', 1)
                ->where('cards.responses_ready_to_become_scripts', 1)
                ->where('cards.scripts_needing_review', 1)
            );
    }

    #[Test]
    public function prompt_run_script_origin_links_are_kept_in_sync(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);
        $type = BulletinType::factory()->create();

        $run = BulletinPromptRun::factory()->create([
            'bulletin_type_id' => $type->id,
            'status' => 'response_received',
            'ai_response_text' => "TITLE:\nDemo\n\nSCRIPT:\nBody",
            'parsed_response' => [
                'title' => 'Demo',
                'intro' => 'Intro',
                'outro' => 'Outro',
                'items' => [['headline' => 'One', 'script' => 'Body']],
            ],
        ]);

        $this->actingAs($editor)->post(route('editor.bulletin-prompt-runs.create-script', $run))->assertRedirect();

        $run->refresh();
        $script = Script::query()->findOrFail($run->script_id);

        $this->assertSame($script->id, $run->script_id);
        $this->assertSame($run->id, $script->bulletin_prompt_run_id);

        $this->actingAs($editor)
            ->get(route('editor.scripts.show', $script))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('script.origin.prompt_run.id', $run->id)
            );

        $this->actingAs($editor)
            ->get(route('editor.bulletin-prompt-runs.show', $run))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('run.script.id', $script->id)
            );
    }

    #[Test]
    public function editor_can_update_and_mark_script_ready_for_production_with_validation(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);
        $viewer = $this->createUserWithPermissions(['viewer.access']);
        $script = Script::factory()->create();

        $this->actingAs($viewer)
            ->put(route('editor.scripts.production.update', $script), ['final_title' => 'No'])
            ->assertForbidden();

        $this->actingAs($editor)
            ->put(route('editor.scripts.production.update', $script), ['mark_ready_for_production' => true])
            ->assertSessionHasErrors(['final_title']);

        $this->actingAs($editor)
            ->put(route('editor.scripts.production.update', $script), [
                'production_name' => 'Bulletin One',
                'public_description' => 'Description',
                'hashtags' => 'news, #world, news',
                'target_platforms' => ['youtube_shorts', 'website'],
                'production_status' => 'metadata_pending',
            ])->assertSessionHasNoErrors();

        $script->refresh();
        $this->assertSame(['#news', '#world'], $script->hashtags);
        $this->assertSame(['youtube_shorts', 'website'], $script->target_platforms);

        $this->actingAs($editor)
            ->put(route('editor.scripts.production.update', $script), [
                'production_name' => 'Bulletin One',
                'public_description' => 'Description',
                'hashtags' => '#news,#world',
                'target_platforms' => ['youtube_shorts'],
                'mark_ready_for_production' => true,
            ])->assertSessionHasNoErrors();

        $script->refresh();
        $this->assertSame('ready_for_production', $script->production_status);
        $this->assertNotNull($script->ready_for_production_at);
        $this->assertSame($editor->id, $script->ready_for_production_by);
    }
}
