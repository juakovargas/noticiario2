<?php

namespace Tests\Feature;

use App\Models\Script;
use App\Models\ScriptReviewItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class ScriptReviewWorkflowTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    public function test_editor_can_access_script_review_page(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $script = Script::factory()->create();

        $this->actingAs($editor)
            ->get(route('editor.scripts.review', $script))
            ->assertOk();
    }

    public function test_viewer_cannot_access_script_review_page(): void
    {
        $viewer = $this->createUserWithPermissions(['viewer.access', 'viewer.dashboard.view']);
        $script = Script::factory()->create();

        $this->actingAs($viewer)
            ->get(route('editor.scripts.review', $script))
            ->assertForbidden();
    }

    public function test_user_without_editor_access_cannot_access_script_review_routes(): void
    {
        $user = $this->createUserWithPermissions([]);
        $script = Script::factory()->create();

        $this->actingAs($user)
            ->post(route('editor.scripts.review.mark-in-review', $script))
            ->assertForbidden();
    }

    public function test_editor_can_generate_review_items_for_a_script_and_does_not_duplicate_on_second_generation(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $script = Script::factory()->create([
            'intro' => 'Intro text',
            'body' => "Block one\n\nBlock two",
            'outro' => 'Outro text',
        ]);

        $this->actingAs($editor)->post(route('editor.scripts.review.generate-items', $script))->assertRedirect();
        $firstCount = ScriptReviewItem::query()->where('script_id', $script->id)->count();

        $this->actingAs($editor)->post(route('editor.scripts.review.generate-items', $script))->assertRedirect();
        $secondCount = ScriptReviewItem::query()->where('script_id', $script->id)->count();

        $this->assertGreaterThanOrEqual(3, $firstCount);
        $this->assertSame($firstCount, $secondCount);
    }

    public function test_intro_body_outro_create_expected_review_items(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $script = Script::factory()->create([
            'intro' => 'Intro line',
            'body' => "One\n\nTwo",
            'outro' => 'Outro line',
        ]);

        $this->actingAs($editor)->post(route('editor.scripts.review.generate-items', $script))->assertRedirect();

        $this->assertDatabaseHas('script_review_items', ['script_id' => $script->id, 'type' => 'intro']);
        $this->assertDatabaseHas('script_review_items', ['script_id' => $script->id, 'type' => 'outro']);
        $this->assertDatabaseHas('script_review_items', ['script_id' => $script->id, 'type' => 'script_block']);
    }

    public function test_unstructured_body_creates_at_least_one_review_item(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $script = Script::factory()->create(['intro' => null, 'body' => 'Single block body', 'outro' => null]);

        $this->actingAs($editor)->post(route('editor.scripts.review.generate-items', $script))->assertRedirect();

        $this->assertGreaterThanOrEqual(1, ScriptReviewItem::query()->where('script_id', $script->id)->count());
    }

    public function test_editor_can_update_review_item_fields(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $script = Script::factory()->create();
        $item = ScriptReviewItem::query()->create(['script_id' => $script->id, 'title' => 'Item']);

        $this->actingAs($editor)
            ->put(route('editor.scripts.review-items.update', [$script, $item]), [
                'verification_status' => 'needs_changes',
                'verification_notes' => 'Need rewrite',
                'required_action' => 'rewrite',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('script_review_items', [
            'id' => $item->id,
            'verification_status' => 'needs_changes',
            'verification_notes' => 'Need rewrite',
            'required_action' => 'rewrite',
        ]);
    }

    public function test_item_must_belong_to_script(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $scriptA = Script::factory()->create();
        $scriptB = Script::factory()->create();
        $item = ScriptReviewItem::query()->create(['script_id' => $scriptB->id, 'title' => 'Wrong owner']);

        $this->actingAs($editor)
            ->put(route('editor.scripts.review-items.update', [$scriptA, $item]), [
                'verification_status' => 'verified',
            ])
            ->assertNotFound();
    }

    public function test_editor_can_mark_script_in_review(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $script = Script::factory()->create(['review_status' => 'pending']);

        $this->actingAs($editor)->post(route('editor.scripts.review.mark-in-review', $script))->assertRedirect();

        $script->refresh();
        $this->assertSame('in_review', $script->review_status);
        $this->assertNotNull($script->reviewed_by);
        $this->assertNotNull($script->reviewed_at);
    }

    public function test_editor_can_mark_script_verified_when_items_are_verified(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $script = Script::factory()->create(['review_status' => 'in_review']);
        ScriptReviewItem::query()->create(['script_id' => $script->id, 'verification_status' => 'verified']);

        $this->actingAs($editor)->post(route('editor.scripts.review.mark-verified', $script))->assertRedirect();

        $this->assertDatabaseHas('scripts', ['id' => $script->id, 'review_status' => 'verified']);
    }

    public function test_editor_cannot_approve_script_with_unresolved_review_issues(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $script = Script::factory()->create(['review_status' => 'in_review']);
        ScriptReviewItem::query()->create(['script_id' => $script->id, 'verification_status' => 'needs_source']);

        $this->actingAs($editor)->post(route('editor.scripts.review.approve', $script))->assertRedirect();

        $this->assertDatabaseMissing('scripts', ['id' => $script->id, 'review_status' => 'approved']);
    }

    public function test_editor_can_approve_verified_script_and_sets_approval_fields(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $script = Script::factory()->create(['review_status' => 'verified']);

        $this->actingAs($editor)->post(route('editor.scripts.review.approve', $script))->assertRedirect();

        $script->refresh();
        $this->assertSame('approved', $script->review_status);
        $this->assertSame('approved', $script->status);
        $this->assertNotNull($script->approved_by);
        $this->assertNotNull($script->approved_at);
    }

    public function test_editor_can_reject_script_with_rejection_reason_and_sets_rejection_fields(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $script = Script::factory()->create(['review_status' => 'in_review']);

        $this->actingAs($editor)
            ->post(route('editor.scripts.review.reject', $script), ['rejection_reason' => 'Missing key source'])
            ->assertRedirect();

        $script->refresh();
        $this->assertSame('rejected', $script->review_status);
        $this->assertSame('rejected', $script->status);
        $this->assertSame('Missing key source', $script->rejection_reason);
        $this->assertNotNull($script->rejected_by);
        $this->assertNotNull($script->rejected_at);
    }

    public function test_rejecting_without_reason_fails_validation(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $script = Script::factory()->create(['review_status' => 'in_review']);

        $this->actingAs($editor)
            ->from(route('editor.scripts.review', $script))
            ->post(route('editor.scripts.review.reject', $script), ['rejection_reason' => ''])
            ->assertRedirect(route('editor.scripts.review', $script))
            ->assertSessionHasErrors('rejection_reason');
    }

    public function test_dashboard_contains_scripts_needing_review_buckets(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        Script::factory()->create(['review_status' => 'needs_sources']);

        $this->actingAs($editor)
            ->get(route('editor.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('stats.scriptsPendingReview')
                ->has('stats.scriptsNeedingSources')
                ->has('stats.scriptsApprovedToday')
                ->has('scriptsNeedingReview')
                ->has('readyToApprove')
            );
    }

    public function test_demo_seeders_create_review_scripts_and_items_without_duplicates(): void
    {
        $this->seed([\Database\Seeders\RolesAndPermissionsSeeder::class, \Database\Seeders\DemoEditorialSeeder::class]);

        $this->assertDatabaseHas('scripts', ['review_status' => 'pending']);
        $this->assertDatabaseHas('scripts', ['review_status' => 'in_review']);
        $this->assertDatabaseHas('scripts', ['review_status' => 'verified']);

        $count = ScriptReviewItem::query()->count();

        $this->seed([\Database\Seeders\RolesAndPermissionsSeeder::class, \Database\Seeders\DemoEditorialSeeder::class]);

        $this->assertSame($count, ScriptReviewItem::query()->count());
    }
}
