<?php

namespace Tests\Unit;

use App\Models\BulletinPromptRun;
use App\Models\BulletinType;
use App\Models\Location;
use App\Services\Maps\BulletinMapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulletinMapServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_returns_markers_for_active_bulletins_with_coordinates_and_excludes_inactive_for_editor(): void
    {
        $location = Location::factory()->create(['latitude' => 1, 'longitude' => 2, 'show_on_map' => true]);
        $active = BulletinType::factory()->create(['location_id' => $location->id, 'is_active' => true]);
        BulletinType::factory()->create(['location_id' => $location->id, 'is_active' => false]);
        BulletinPromptRun::factory()->create(['bulletin_type_id' => $active->id, 'status' => 'prompt_ready']);

        $data = app(BulletinMapService::class)->getEditorMapData();

        $this->assertCount(1, $data);
        $this->assertSame(1, $data[0]['bulletin_count']);
        $this->assertSame(1, $data[0]['pending_prompt_runs_count']);
    }

    public function test_service_excludes_locations_without_coordinates(): void
    {
        $location = Location::factory()->create(['latitude' => null, 'longitude' => null]);
        BulletinType::factory()->create(['location_id' => $location->id, 'is_active' => true]);

        $data = app(BulletinMapService::class)->getEditorMapData();

        $this->assertCount(0, $data);
    }

    public function test_service_excludes_archived_runs_from_active_counters_and_returns_bulletins_and_viewer_without_urls(): void
    {
        $location = Location::factory()->create(['latitude' => 10, 'longitude' => 10, 'show_on_map' => true]);
        $bulletin = BulletinType::factory()->create(['location_id' => $location->id, 'is_active' => true]);
        BulletinPromptRun::factory()->create(['bulletin_type_id' => $bulletin->id, 'status' => 'archived']);
        BulletinPromptRun::factory()->create(['bulletin_type_id' => $bulletin->id, 'status' => 'completed']);

        $editor = app(BulletinMapService::class)->getEditorMapData();
        $viewer = app(BulletinMapService::class)->getViewerMapData();

        $this->assertSame(1, $editor[0]['completed_prompt_runs_count']);
        $this->assertSame(1, $editor[0]['archived_prompt_runs_count']);
        $this->assertCount(1, $editor[0]['bulletins']);
        $this->assertNull($viewer[0]['bulletins'][0]['url']);
    }
}
