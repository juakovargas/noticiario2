<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class LocationDefaultLanguageTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    public function test_editor_can_create_location_with_default_language(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $language = Language::factory()->create(['code' => 'es']);

        $this->actingAs($editor)->post(route('editor.locations.store'), [
            'name' => 'Chile',
            'type' => 'country',
            'default_language_id' => $language->id,
        ])->assertRedirect(route('editor.locations.index'));

        $this->assertDatabaseHas('locations', ['name' => 'Chile', 'default_language_id' => $language->id]);
    }

    public function test_deleting_language_nulls_location_default_language_id(): void
    {
        $language = Language::factory()->create(['code' => 'fr']);
        $location = Location::factory()->create(['default_language_id' => $language->id]);

        $language->delete();

        $this->assertDatabaseHas('locations', ['id' => $location->id, 'default_language_id' => null]);
    }
}
