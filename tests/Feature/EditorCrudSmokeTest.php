<?php

namespace Tests\Feature;

use App\Models\Edition;
use App\Models\Location;
use App\Models\NewsCategory;
use App\Models\NewsSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class EditorCrudSmokeTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    public function test_editor_can_create_location_and_slug_is_generated_when_omitted(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);

        $this->actingAs($editor)
            ->post(route('editor.locations.store'), [
                'name' => 'Bogota',
                'type' => 'city',
                'country_code' => 'CO',
                'timezone' => 'America/Bogota',
            ])
            ->assertRedirect(route('editor.locations.index'));

        $this->assertDatabaseHas('locations', [
            'name' => 'Bogota',
            'slug' => 'bogota',
            'type' => 'city',
        ]);
    }

    public function test_editor_can_update_location(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $location = Location::factory()->create([
            'name' => 'Old Name',
            'slug' => 'old-name',
        ]);

        $this->actingAs($editor)
            ->put(route('editor.locations.update', $location), [
                'name' => 'New Name',
                'slug' => '',
                'type' => 'city',
                'country_code' => 'US',
                'timezone' => 'America/Chicago',
                'is_active' => true,
                'sort_order' => 1,
            ])
            ->assertRedirect(route('editor.locations.index'));

        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'name' => 'New Name',
            'slug' => 'new-name',
            'sort_order' => 1,
        ]);
    }

    public function test_editor_can_create_news_category_and_slug_is_generated_when_omitted(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);

        $this->actingAs($editor)
            ->post(route('editor.news-categories.store'), [
                'name' => 'Breaking News',
            ])
            ->assertRedirect(route('editor.news-categories.index'));

        $this->assertDatabaseHas('news_categories', [
            'name' => 'Breaking News',
            'slug' => 'breaking-news',
        ]);
    }

    public function test_editor_can_create_manual_news_source(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);

        $this->actingAs($editor)
            ->post(route('editor.news-sources.store'), [
                'name' => 'Manual Desk',
                'type' => 'manual',
                'language' => 'es',
            ])
            ->assertRedirect(route('editor.news-sources.index'));

        $this->assertDatabaseHas('news_sources', [
            'name' => 'Manual Desk',
            'type' => 'manual',
            'slug' => 'manual-desk',
        ]);
    }

    public function test_editor_can_create_news_item_linked_to_source_category_and_location(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $location = Location::factory()->create();
        $category = NewsCategory::factory()->create();
        $source = NewsSource::factory()->create();

        $this->actingAs($editor)
            ->post(route('editor.news-items.store'), [
                'news_source_id' => $source->id,
                'news_category_id' => $category->id,
                'location_id' => $location->id,
                'title' => 'Item for Tests',
                'status' => 'draft',
                'is_evergreen' => false,
            ])
            ->assertRedirect(route('editor.news-items.index'));

        $this->assertDatabaseHas('news_items', [
            'title' => 'Item for Tests',
            'slug' => 'item-for-tests',
            'news_source_id' => $source->id,
            'news_category_id' => $category->id,
            'location_id' => $location->id,
        ]);
    }

    public function test_editor_can_create_edition_linked_to_location(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $location = Location::factory()->create();

        $this->actingAs($editor)
            ->post(route('editor.editions.store'), [
                'location_id' => $location->id,
                'title' => 'Morning Brief',
                'edition_type' => 'morning',
                'status' => 'draft',
            ])
            ->assertRedirect(route('editor.editions.index'));

        $this->assertDatabaseHas('editions', [
            'title' => 'Morning Brief',
            'slug' => 'morning-brief',
            'location_id' => $location->id,
        ]);
    }

    public function test_editor_can_create_script_linked_to_edition(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $edition = Edition::factory()->create();

        $this->actingAs($editor)
            ->post(route('editor.scripts.store'), [
                'edition_id' => $edition->id,
                'title' => 'Script Draft',
                'status' => 'draft',
            ])
            ->assertRedirect(route('editor.scripts.index'));

        $this->assertDatabaseHas('scripts', [
            'edition_id' => $edition->id,
            'title' => 'Script Draft',
            'status' => 'draft',
        ]);
    }
}
