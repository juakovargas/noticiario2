<?php

namespace Tests\Feature;

use App\Models\AiProvider;
use App\Models\BulletinType;
use App\Models\EditorialSchedule;
use App\Models\EditorialScheduleRun;
use App\Models\Location;
use App\Models\NewsCategory;
use App\Models\Script;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditorDashboardOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_structured_operational_sections(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['editor.access', 'editor.dashboard.view']);

        $provider = AiProvider::factory()->create(['name' => 'Gemini Grounded', 'default_model' => 'gemini-3-flash-preview', 'supports_grounding' => true]);
        $location = Location::factory()->create();
        $category = NewsCategory::factory()->create();
        $bulletin = BulletinType::factory()->create(['is_active' => true, 'location_id' => $location->id, 'news_category_id' => $category->id, 'ai_provider_id' => $provider->id]);
        $schedule = EditorialSchedule::factory()->create(['bulletin_type_id' => $bulletin->id, 'location_id' => $location->id, 'news_category_id' => $category->id, 'is_primary' => true, 'is_active' => true, 'run_frequency' => 'daily', 'run_time' => '08:00:00', 'next_run_at' => now()->addHour()]);
        EditorialScheduleRun::factory()->create(['editorial_schedule_id' => $schedule->id, 'status' => 'completed', 'scheduled_for' => now()->subHour()]);

        $this->actingAs($user)->get(route('editor.dashboard'))->assertOk()->assertInertia(fn ($page) => $page
            ->component('Editor/Dashboard')->has('summary')->has('mapOverview.locations')->has('actionableQueue')->has('scheduledBulletins.on')->has('coverageOverview.groups')->has('aiProviderStatus')->has('latestExecutions')
        );
    }



    public function test_dashboard_handles_scripts_without_direct_bulletin_type_relation(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['editor.access', 'editor.dashboard.view']);

        Script::factory()->create([
            'review_status' => 'pending',
            'status' => 'draft',
            'bulletin_prompt_run_id' => null,
        ]);

        $this->actingAs($user)->get(route('editor.dashboard'))->assertOk();
    }

    public function test_actionable_queue_excludes_disabled_without_attention_and_includes_provider_model_and_on_off(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['editor.access', 'editor.dashboard.view']);

        $provider = AiProvider::factory()->create(['name' => 'Gemini Grounded', 'default_model' => 'gemini-2.5-pro']);
        $location = Location::factory()->create();
        $category = NewsCategory::factory()->create();

        $activeBulletin = BulletinType::factory()->create(['is_active' => true, 'location_id' => $location->id, 'news_category_id' => $category->id, 'ai_provider_id' => $provider->id]);
        EditorialSchedule::factory()->create(['bulletin_type_id' => $activeBulletin->id, 'location_id' => $location->id, 'news_category_id' => $category->id, 'is_primary' => true, 'is_active' => true, 'run_frequency' => 'daily', 'run_time' => '08:00:00', 'next_run_at' => now()->subHour()]);

        $disabledBulletin = BulletinType::factory()->create(['is_active' => false, 'location_id' => $location->id, 'news_category_id' => $category->id, 'ai_provider_id' => $provider->id]);
        EditorialSchedule::factory()->create(['bulletin_type_id' => $disabledBulletin->id, 'location_id' => $location->id, 'news_category_id' => $category->id, 'is_primary' => true, 'is_active' => false, 'run_frequency' => 'daily', 'run_time' => '10:00:00', 'next_run_at' => now()->addDay()]);

        $props = $this->actingAs($user)->get(route('editor.dashboard'))->viewData('page')['props'];

        $queueBulletinIds = collect($props['actionableQueue'])->pluck('bulletin_id')->filter()->all();
        $this->assertContains($activeBulletin->id, $queueBulletinIds);
        $this->assertNotContains($disabledBulletin->id, $queueBulletinIds);

        $scheduledOn = collect($props['scheduledBulletins']['on'])->firstWhere('bulletin', $activeBulletin->name);
        $this->assertNotNull($scheduledOn);
        $this->assertTrue((bool) $scheduledOn['is_on']);
        $this->assertSame('Gemini Grounded', $scheduledOn['provider']);
        $this->assertSame('gemini-2.5-pro', $scheduledOn['model']);
    }
}
