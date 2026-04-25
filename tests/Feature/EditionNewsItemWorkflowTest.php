<?php

namespace Tests\Feature;

use App\Models\Edition;
use App\Models\NewsItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class EditionNewsItemWorkflowTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    public function test_editor_can_attach_news_item_to_edition_and_store_pivot_data(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $edition = Edition::factory()->create();
        $newsItem = NewsItem::factory()->create();

        $this->actingAs($editor)
            ->post(route('editor.editions.news-items.store', $edition), [
                'news_item_id' => $newsItem->id,
                'sort_order' => 2,
                'editorial_angle' => 'Lead with local context',
                'included_in_script' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('edition_news_item', [
            'edition_id' => $edition->id,
            'news_item_id' => $newsItem->id,
            'sort_order' => 2,
            'editorial_angle' => 'Lead with local context',
            'included_in_script' => true,
        ]);
    }

    public function test_editor_cannot_attach_same_news_item_twice_to_same_edition(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $edition = Edition::factory()->create();
        $newsItem = NewsItem::factory()->create();

        $edition->newsItems()->attach($newsItem->id, [
            'sort_order' => 1,
            'editorial_angle' => 'Existing angle',
            'included_in_script' => true,
        ]);

        $response = $this->from(route('editor.editions.show', $edition))
            ->actingAs($editor)
            ->post(route('editor.editions.news-items.store', $edition), [
                'news_item_id' => $newsItem->id,
                'included_in_script' => false,
            ]);

        $response->assertRedirect(route('editor.editions.show', $edition));
        $response->assertSessionHasErrors('news_item_id');
        $this->assertDatabaseCount('edition_news_item', 1);
    }

    public function test_editor_can_update_edition_news_item_pivot_data(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $edition = Edition::factory()->create();
        $newsItem = NewsItem::factory()->create();

        $edition->newsItems()->attach($newsItem->id, [
            'sort_order' => 1,
            'editorial_angle' => 'Initial',
            'included_in_script' => true,
        ]);

        $this->actingAs($editor)
            ->put(route('editor.editions.news-items.update', [$edition, $newsItem]), [
                'sort_order' => 5,
                'editorial_angle' => 'Updated angle',
                'included_in_script' => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('edition_news_item', [
            'edition_id' => $edition->id,
            'news_item_id' => $newsItem->id,
            'sort_order' => 5,
            'editorial_angle' => 'Updated angle',
            'included_in_script' => false,
        ]);
    }

    public function test_editor_can_detach_news_item_from_edition(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $edition = Edition::factory()->create();
        $newsItem = NewsItem::factory()->create();

        $edition->newsItems()->attach($newsItem->id, [
            'sort_order' => 1,
            'included_in_script' => true,
        ]);

        $this->actingAs($editor)
            ->delete(route('editor.editions.news-items.destroy', [$edition, $newsItem]))
            ->assertRedirect();

        $this->assertDatabaseMissing('edition_news_item', [
            'edition_id' => $edition->id,
            'news_item_id' => $newsItem->id,
        ]);
    }

    public function test_viewer_cannot_access_edition_news_item_mutation_routes(): void
    {
        $this->ensurePermissionsExist(['viewer.access', 'viewer.dashboard.view', 'editor.access', 'editor.dashboard.view']);

        $viewer = $this->createUserWithPermissions(['viewer.access', 'viewer.dashboard.view']);
        $edition = Edition::factory()->create();
        $newsItem = NewsItem::factory()->create();

        $this->actingAs($viewer)
            ->post(route('editor.editions.news-items.store', $edition), [
                'news_item_id' => $newsItem->id,
                'included_in_script' => true,
            ])
            ->assertForbidden();

        $this->actingAs($viewer)
            ->put(route('editor.editions.news-items.update', [$edition, $newsItem]), [
                'included_in_script' => true,
            ])
            ->assertForbidden();

        $this->actingAs($viewer)
            ->delete(route('editor.editions.news-items.destroy', [$edition, $newsItem]))
            ->assertForbidden();
    }
}
