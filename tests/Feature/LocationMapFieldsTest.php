<?php

namespace Tests\Feature;

use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationMapFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_location_can_store_map_fields(): void
    {
        $location = Location::factory()->create([
            'latitude' => 40.4167750,
            'longitude' => -3.7037900,
            'map_zoom' => 5,
            'show_on_map' => true,
        ]);

        $this->assertSame(40.416775, $location->fresh()->latitude);
        $this->assertSame(-3.70379, $location->fresh()->longitude);
        $this->assertSame(5, $location->fresh()->map_zoom);
        $this->assertTrue($location->fresh()->show_on_map);
    }

    public function test_location_without_coordinates_is_valid(): void
    {
        $location = Location::factory()->create([
            'latitude' => null,
            'longitude' => null,
        ]);

        $this->assertNotNull($location->id);
        $this->assertNull($location->fresh()->latitude);
        $this->assertNull($location->fresh()->longitude);
    }
}
