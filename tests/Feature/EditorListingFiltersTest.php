<?php

namespace Tests\Feature;

use App\Models\Edition;
use App\Models\Language;
use App\Models\Location;
use App\Models\NewsCategory;
use App\Models\NewsItem;
use App\Models\NewsSource;
use App\Models\Script;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class EditorListingFiltersTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    public function test_editor_can_filter_news_items_by_search(): void
    {
        $editor = $this->createEditor();
        NewsItem::factory()->create(['title' => 'Climate Alert Morning', 'status' => 'draft']);
        NewsItem::factory()->create(['title' => 'Sports Segment', 'status' => 'draft']);

        $this->actingAs($editor)
            ->get(route('editor.news-items.index', ['search' => 'Climate']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.search', 'Climate')
                ->has('newsItems.data', 1)
                ->where('newsItems.data.0.title', 'Climate Alert Morning')
            );
    }

    public function test_editor_can_filter_news_items_by_status(): void
    {
        $editor = $this->createEditor();
        NewsItem::factory()->create(['title' => 'Selected Story', 'status' => 'selected']);
        NewsItem::factory()->create(['title' => 'Draft Story', 'status' => 'draft']);

        $this->actingAs($editor)
            ->get(route('editor.news-items.index', ['status' => 'selected']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.status', 'selected')
                ->has('newsItems.data', 1)
                ->where('newsItems.data.0.status', 'selected')
            );
    }

    public function test_editor_can_filter_news_items_by_category(): void
    {
        $editor = $this->createEditor();
        $category = NewsCategory::factory()->create(['name' => 'Politics']);
        $otherCategory = NewsCategory::factory()->create(['name' => 'Sports']);

        NewsItem::factory()->create(['title' => 'Policy Changes', 'news_category_id' => $category->id]);
        NewsItem::factory()->create(['title' => 'League News', 'news_category_id' => $otherCategory->id]);

        $this->actingAs($editor)
            ->get(route('editor.news-items.index', ['news_category_id' => (string) $category->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.news_category_id', (string) $category->id)
                ->has('newsItems.data', 1)
                ->where('newsItems.data.0.title', 'Policy Changes')
            );
    }

    public function test_editor_can_filter_news_items_by_location(): void
    {
        $editor = $this->createEditor();
        $location = Location::factory()->create(['name' => 'New York']);
        $otherLocation = Location::factory()->create(['name' => 'Madrid']);

        NewsItem::factory()->create(['title' => 'US Update', 'location_id' => $location->id]);
        NewsItem::factory()->create(['title' => 'Spain Update', 'location_id' => $otherLocation->id]);

        $this->actingAs($editor)
            ->get(route('editor.news-items.index', ['location_id' => (string) $location->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.location_id', (string) $location->id)
                ->has('newsItems.data', 1)
                ->where('newsItems.data.0.title', 'US Update')
            );
    }

    public function test_editor_can_sort_news_items(): void
    {
        $editor = $this->createEditor();

        NewsItem::factory()->create(['title' => 'Zulu Story']);
        NewsItem::factory()->create(['title' => 'Alpha Story']);

        $this->actingAs($editor)
            ->get(route('editor.news-items.index', ['sort' => 'title', 'direction' => 'asc']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.sort', 'title')
                ->where('filters.direction', 'asc')
                ->where('newsItems.data.0.title', 'Alpha Story')
            );
    }

    public function test_viewer_cannot_access_news_item_filters(): void
    {
        $viewer = $this->createViewer();

        $this->actingAs($viewer)
            ->get(route('editor.news-items.index', ['search' => 'anything']))
            ->assertForbidden();
    }

    public function test_editor_can_filter_editions_by_search(): void
    {
        $editor = $this->createEditor();

        Edition::factory()->create(['title' => 'Morning Digest']);
        Edition::factory()->create(['title' => 'Evening Wrap']);

        $this->actingAs($editor)
            ->get(route('editor.editions.index', ['search' => 'Morning']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.search', 'Morning')
                ->has('editions.data', 1)
                ->where('editions.data.0.title', 'Morning Digest')
            );
    }

    public function test_editor_can_filter_editions_by_status(): void
    {
        $editor = $this->createEditor();

        Edition::factory()->create(['title' => 'Approved Edition', 'status' => 'approved']);
        Edition::factory()->create(['title' => 'Draft Edition', 'status' => 'draft']);

        $this->actingAs($editor)
            ->get(route('editor.editions.index', ['status' => 'approved']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.status', 'approved')
                ->has('editions.data', 1)
                ->where('editions.data.0.status', 'approved')
            );
    }

    public function test_editor_can_filter_editions_by_type(): void
    {
        $editor = $this->createEditor();

        Edition::factory()->create(['title' => 'Morning Edition', 'edition_type' => 'morning']);
        Edition::factory()->create(['title' => 'Special Edition', 'edition_type' => 'special']);

        $this->actingAs($editor)
            ->get(route('editor.editions.index', ['edition_type' => 'special']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.edition_type', 'special')
                ->has('editions.data', 1)
                ->where('editions.data.0.edition_type', 'special')
            );
    }

    public function test_editor_can_filter_editions_by_location(): void
    {
        $editor = $this->createEditor();
        $location = Location::factory()->create(['name' => 'Bogota']);
        $otherLocation = Location::factory()->create(['name' => 'Lima']);

        Edition::factory()->create(['title' => 'Bogota Noon', 'location_id' => $location->id]);
        Edition::factory()->create(['title' => 'Lima Noon', 'location_id' => $otherLocation->id]);

        $this->actingAs($editor)
            ->get(route('editor.editions.index', ['location_id' => (string) $location->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.location_id', (string) $location->id)
                ->has('editions.data', 1)
                ->where('editions.data.0.title', 'Bogota Noon')
            );
    }

    public function test_editor_can_sort_editions(): void
    {
        $editor = $this->createEditor();

        Edition::factory()->create(['title' => 'Zulu Edition']);
        Edition::factory()->create(['title' => 'Alpha Edition']);

        $this->actingAs($editor)
            ->get(route('editor.editions.index', ['sort' => 'title', 'direction' => 'asc']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.sort', 'title')
                ->where('filters.direction', 'asc')
                ->where('editions.data.0.title', 'Alpha Edition')
            );
    }

    public function test_viewer_cannot_access_edition_filters(): void
    {
        $viewer = $this->createViewer();

        $this->actingAs($viewer)
            ->get(route('editor.editions.index', ['search' => 'Morning']))
            ->assertForbidden();
    }

    public function test_editor_can_filter_scripts_by_status(): void
    {
        $editor = $this->createEditor();

        Script::factory()->create(['status' => 'approved']);
        Script::factory()->create(['status' => 'draft']);

        $this->actingAs($editor)
            ->get(route('editor.scripts.index', ['status' => 'approved']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.status', 'approved')
                ->has('scripts.data', 1)
                ->where('scripts.data.0.status', 'approved')
            );
    }

    public function test_editor_can_filter_scripts_by_edition(): void
    {
        $editor = $this->createEditor();

        $edition = Edition::factory()->create(['title' => 'Chosen Edition']);
        Script::factory()->create(['edition_id' => $edition->id, 'title' => 'Included Script']);
        Script::factory()->create(['title' => 'Other Script']);

        $this->actingAs($editor)
            ->get(route('editor.scripts.index', ['edition_id' => (string) $edition->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.edition_id', (string) $edition->id)
                ->has('scripts.data', 1)
                ->where('scripts.data.0.title', 'Included Script')
            );
    }

    public function test_editor_can_filter_scripts_by_approved_status(): void
    {
        $editor = $this->createEditor();

        Script::factory()->create(['title' => 'Approved Script', 'approved_at' => now()]);
        Script::factory()->create(['title' => 'Pending Script', 'approved_at' => null]);

        $this->actingAs($editor)
            ->get(route('editor.scripts.index', ['approved' => 'yes']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.approved', 'yes')
                ->has('scripts.data', 1)
                ->where('scripts.data.0.title', 'Approved Script')
            );
    }

    public function test_news_items_and_editions_pages_return_paginated_data(): void
    {
        $editor = $this->createEditor();

        NewsItem::factory()->count(17)->create();
        Edition::factory()->count(16)->create();

        $this->actingAs($editor)
            ->get(route('editor.news-items.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('newsItems.data', 15)
                ->has('newsItems.links')
            );

        $this->actingAs($editor)
            ->get(route('editor.editions.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('editions.data', 15)
                ->has('editions.links')
            );
    }

    public function test_query_string_is_preserved_in_pagination_links_for_news_items(): void
    {
        $editor = $this->createEditor();
        $source = NewsSource::factory()->create();

        NewsItem::factory()->count(17)->create(['news_source_id' => $source->id]);

        $this->actingAs($editor)
            ->get(route('editor.news-items.index', ['news_source_id' => (string) $source->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('newsItems.links.1.url', fn (?string $url) => is_string($url) && str_contains($url, 'news_source_id='.$source->id))
            );
    }

    private function createEditor()
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English']);

        return $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
    }

    private function createViewer()
    {
        return $this->createUserWithPermissions(['viewer.access', 'viewer.dashboard.view']);
    }
}
