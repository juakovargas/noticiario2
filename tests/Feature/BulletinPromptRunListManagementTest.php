<?php

namespace Tests\Feature;

use App\Models\BulletinPromptRun;
use App\Models\BulletinType;
use App\Models\PromptProfile;
use App\Models\Script;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class BulletinPromptRunListManagementTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    #[Test]
    public function index_hides_archived_runs_by_default_and_can_show_them(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);
        $type = BulletinType::factory()->create();

        $active = BulletinPromptRun::factory()->create(['bulletin_type_id' => $type->id, 'status' => 'draft']);
        $archived = BulletinPromptRun::factory()->create(['bulletin_type_id' => $type->id, 'status' => 'archived']);

        $this->actingAs($editor)
            ->get(route('editor.bulletin-prompt-runs.index'))
            ->assertOk()
            ->assertSee($active->title)
            ->assertDontSee($archived->title);

        $this->actingAs($editor)
            ->get(route('editor.bulletin-prompt-runs.index', ['show_archived' => 1]))
            ->assertOk()
            ->assertSee($archived->title);
    }

    #[Test]
    public function index_filters_by_status_bulletin_type_and_prompt_profile(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);

        $wantedType = BulletinType::factory()->create();
        $otherType = BulletinType::factory()->create();
        $wantedProfile = PromptProfile::query()->create(['name' => 'Wanted', 'slug' => 'wanted', 'is_active' => true]);
        $otherProfile = PromptProfile::query()->create(['name' => 'Other', 'slug' => 'other', 'is_active' => true]);

        $wantedRun = BulletinPromptRun::factory()->create([
            'bulletin_type_id' => $wantedType->id,
            'prompt_profile_id' => $wantedProfile->id,
            'status' => 'response_received',
        ]);

        $otherRun = BulletinPromptRun::factory()->create([
            'bulletin_type_id' => $otherType->id,
            'prompt_profile_id' => $otherProfile->id,
            'status' => 'draft',
        ]);

        $this->actingAs($editor)
            ->get(route('editor.bulletin-prompt-runs.index', [
                'status' => 'response_received',
                'bulletin_type_id' => $wantedType->id,
                'prompt_profile_id' => $wantedProfile->id,
            ]))
            ->assertOk()
            ->assertSee($wantedRun->title)
            ->assertDontSee($otherRun->title);
    }

    #[Test]
    public function editor_can_archive_restore_complete_and_cancel_run(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);
        $type = BulletinType::factory()->create();

        $draft = BulletinPromptRun::factory()->create(['bulletin_type_id' => $type->id, 'status' => 'draft']);
        $ready = BulletinPromptRun::factory()->create(['bulletin_type_id' => $type->id, 'status' => 'archived', 'generated_prompt' => 'prompt']);
        $responseRun = BulletinPromptRun::factory()->create(['bulletin_type_id' => $type->id, 'status' => 'response_received']);

        $this->actingAs($editor)->post(route('editor.bulletin-prompt-runs.archive', $draft))->assertRedirect();
        $this->assertSame('archived', $draft->fresh()->status);

        $this->actingAs($editor)->post(route('editor.bulletin-prompt-runs.restore', $ready))->assertRedirect();
        $this->assertSame('prompt_ready', $ready->fresh()->status);

        $this->actingAs($editor)->post(route('editor.bulletin-prompt-runs.mark-completed', $responseRun))->assertRedirect();
        $this->assertSame('completed', $responseRun->fresh()->status);

        $this->actingAs($editor)->post(route('editor.bulletin-prompt-runs.cancel', $responseRun))->assertRedirect();
        $this->assertSame('cancelled', $responseRun->fresh()->status);
    }

    #[Test]
    public function viewer_cannot_archive_restore_or_cancel_runs(): void
    {
        $viewer = $this->createUserWithPermissions(['viewer.access']);
        $run = BulletinPromptRun::factory()->create();

        $this->actingAs($viewer)->post(route('editor.bulletin-prompt-runs.archive', $run))->assertForbidden();
        $this->actingAs($viewer)->post(route('editor.bulletin-prompt-runs.restore', $run))->assertForbidden();
        $this->actingAs($viewer)->post(route('editor.bulletin-prompt-runs.cancel', $run))->assertForbidden();
    }

    #[Test]
    public function archived_prompt_runs_are_excluded_from_dashboard_active_counts(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);

        BulletinPromptRun::factory()->create(['status' => 'archived']);
        BulletinPromptRun::factory()->create(['status' => 'prompt_ready']);

        $response = $this->actingAs($editor)->get(route('editor.dashboard'));

        $response->assertOk();
        $stats = $response->viewData('page')['props']['stats'] ?? [];
        $this->assertSame(1, $stats['recentPromptRuns']);
    }
}
