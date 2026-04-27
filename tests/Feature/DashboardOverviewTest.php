<?php

namespace Tests\Feature;

use App\Models\EditorialSchedule;
use App\Models\EditorialScheduleRun;
use App\Models\Script;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class DashboardOverviewTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    public function test_editor_dashboard_returns_todays_runs_and_pending_prompt_counts(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);

        $schedule = EditorialSchedule::query()->create([
            'name' => 'Desk Demo',
            'slug' => 'desk-demo',
            'edition_type' => 'morning',
            'frequency_type' => 'daily',
            'manual_ai_mode' => true,
            'is_active' => true,
        ]);

        EditorialScheduleRun::query()->create([
            'editorial_schedule_id' => $schedule->id,
            'status' => 'pending',
            'scheduled_for' => now(),
        ]);

        $this->actingAs($editor)
            ->get(route('editor.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Editor/Dashboard')
                ->where('stats.todayRuns', 1)
                ->where('stats.pendingPrompts', 1)
            );
    }

    public function test_viewer_cannot_access_editor_dashboard_or_editorial_desk(): void
    {
        $viewer = $this->createUserWithPermissions(['viewer.access', 'viewer.dashboard.view']);

        $this->actingAs($viewer)->get(route('editor.dashboard'))->assertForbidden();
        $this->actingAs($viewer)->get(route('editor.editorial-desk.index'))->assertForbidden();
    }

    public function test_admin_dashboard_renders_when_editorial_tables_are_empty(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access', 'dashboard.view']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Dashboard')
                ->has('stats.activeSchedules')
                ->has('stats.todayRuns')
                ->has('stats.failedRuns')
            );
    }

    public function test_editor_cannot_access_admin_dashboard(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);

        $this->actingAs($editor)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_viewer_dashboard_is_read_only_overview(): void
    {
        $viewer = $this->createUserWithPermissions(['viewer.access', 'viewer.dashboard.view']);

        Script::factory()->create(['status' => 'review']);

        $this->actingAs($viewer)
            ->get(route('viewer.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Viewer/Dashboard')
                ->has('recentCompletedScripts')
                ->has('recentCompletedRuns')
                ->has('upcomingEditions')
            );
    }
}
