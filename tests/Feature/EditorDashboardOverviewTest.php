<?php

namespace Tests\Feature;

use App\Models\BulletinType;
use App\Models\EditorialSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditorDashboardOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_dashboard_loads_and_contains_new_sections(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['editor.access', 'editor.dashboard.view']);

        $bulletin = BulletinType::factory()->create(['is_active' => true]);
        EditorialSchedule::factory()->create(['bulletin_type_id' => $bulletin->id, 'is_active' => true]);

        $this->actingAs($user)
            ->get(route('editor.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Editor/Dashboard')
                ->has('dailySummary')
                ->has('pendingTasks')
                ->has('coverageMatrix')
                ->has('activeBulletins')
                ->has('inactiveBulletins')
                ->has('editorialAlerts')
                ->has('latestRuns')
            );
    }
}
