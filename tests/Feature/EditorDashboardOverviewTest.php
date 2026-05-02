<?php

namespace Tests\Feature;

use App\Models\AiProvider;
use App\Models\BulletinType;
use App\Models\EditorialSchedule;
use App\Models\EditorialScheduleRun;
use App\Models\Location;
use App\Models\NewsCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditorDashboardOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_dashboard_loads_and_contains_refined_sections(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['editor.access', 'editor.dashboard.view']);

        $provider = AiProvider::factory()->create(['name' => 'Gemini Grounded', 'default_model' => 'gemini-2.5-pro', 'supports_grounding' => true]);
        $location = Location::factory()->create();
        $category = NewsCategory::factory()->create();
        $bulletin = BulletinType::factory()->create(['is_active' => true, 'location_id' => $location->id, 'news_category_id' => $category->id, 'ai_provider_id' => $provider->id]);
        $schedule = EditorialSchedule::factory()->create(['bulletin_type_id' => $bulletin->id, 'location_id' => $location->id, 'news_category_id' => $category->id, 'is_active' => true]);
        EditorialScheduleRun::factory()->create(['editorial_schedule_id' => $schedule->id, 'status' => 'completed', 'scheduled_for' => now()->subHour()]);

        $this->actingAs($user)
            ->get(route('editor.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Editor/Dashboard')
                ->has('dailySummary')
                ->has('pendingTasks')
                ->has('coverageMatrix.legend')
                ->has('bulletinGroups.active')
                ->has('bulletinGroups.paused')
                ->has('bulletinGroups.incomplete')
                ->has('bulletinGroups.attention')
                ->has('aiProviders')
                ->has('latestRuns')
            );
    }

    public function test_bulletin_groups_are_exclusive_and_pending_tasks_include_provider_model(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['editor.access', 'editor.dashboard.view']);

        $provider = AiProvider::factory()->create(['name' => 'Groq', 'default_model' => 'llama-3.3']);
        $location = Location::factory()->create();
        $category = NewsCategory::factory()->create();

        $active = BulletinType::factory()->create(['is_active' => true, 'location_id' => $location->id, 'news_category_id' => $category->id, 'ai_provider_id' => $provider->id]);
        EditorialSchedule::factory()->create(['bulletin_type_id' => $active->id, 'location_id' => $location->id, 'news_category_id' => $category->id, 'is_primary' => true, 'is_active' => true, 'next_run_at' => now()->addHour()]);

        $incomplete = BulletinType::factory()->create(['is_active' => true, 'location_id' => $location->id, 'news_category_id' => $category->id, 'ai_provider_id' => null]);
        EditorialSchedule::factory()->create(['bulletin_type_id' => $incomplete->id, 'location_id' => $location->id, 'news_category_id' => $category->id, 'is_primary' => true, 'is_active' => true]);

        $response = $this->actingAs($user)->get(route('editor.dashboard'));
        $response->assertOk();

        $props = $response->viewData('page')['props'];
        $activeIds = collect($props['bulletinGroups']['active'])->pluck('id');
        $incompleteIds = collect($props['bulletinGroups']['incomplete'])->pluck('id');

        $this->assertTrue($activeIds->contains($active->id));
        $this->assertTrue($incompleteIds->contains($incomplete->id));
        $this->assertTrue($activeIds->intersect($incompleteIds)->isEmpty());

        $task = collect($props['pendingTasks'])->firstWhere('bulletin_id', $active->id);
        $this->assertSame('Groq', $task['provider']);
        $this->assertSame('llama-3.3', $task['model']);
    }
}
