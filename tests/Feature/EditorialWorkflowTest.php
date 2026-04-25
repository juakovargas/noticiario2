<?php

namespace Tests\Feature;

use App\Models\Edition;
use App\Models\NewsItem;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditorialWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_editor_user_can_access_edition_show(): void
    {
        $editor = $this->createEditor();
        $edition = Edition::query()->create([
            'title' => 'Morning Edition',
            'slug' => 'morning-edition',
            'edition_type' => 'morning',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($editor)->get(route('editor.editions.show', $edition));

        $response->assertOk();
    }

    public function test_editor_user_can_add_news_item_to_edition(): void
    {
        $editor = $this->createEditor();
        $edition = Edition::query()->create([
            'title' => 'Noon Edition',
            'slug' => 'noon-edition',
            'edition_type' => 'afternoon',
            'status' => 'draft',
        ]);
        $newsItem = $this->createNewsItem('item-one', 'Item One');

        $response = $this->actingAs($editor)->post(route('editor.editions.news-items.store', $edition), [
            'news_item_id' => $newsItem->id,
            'sort_order' => 1,
            'editorial_angle' => 'Focus on local impact',
            'included_in_script' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('edition_news_item', [
            'edition_id' => $edition->id,
            'news_item_id' => $newsItem->id,
            'sort_order' => 1,
            'included_in_script' => true,
        ]);
    }

    public function test_editor_user_cannot_add_duplicate_news_item_to_same_edition(): void
    {
        $editor = $this->createEditor();
        $edition = Edition::query()->create([
            'title' => 'Night Edition',
            'slug' => 'night-edition',
            'edition_type' => 'night',
            'status' => 'draft',
        ]);
        $newsItem = $this->createNewsItem('item-two', 'Item Two');

        $edition->newsItems()->attach($newsItem->id, [
            'sort_order' => 1,
            'editorial_angle' => 'Initial angle',
            'included_in_script' => true,
        ]);

        $response = $this->from(route('editor.editions.show', $edition))
            ->actingAs($editor)
            ->post(route('editor.editions.news-items.store', $edition), [
                'news_item_id' => $newsItem->id,
                'sort_order' => 2,
                'included_in_script' => false,
            ]);

        $response->assertRedirect(route('editor.editions.show', $edition));
        $response->assertSessionHasErrors('news_item_id');
        $this->assertDatabaseCount('edition_news_item', 1);
    }

    public function test_viewer_user_cannot_access_editor_edition_news_item_routes(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('viewer');

        $edition = Edition::query()->create([
            'title' => 'Viewer Edition',
            'slug' => 'viewer-edition',
            'edition_type' => 'morning',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($viewer)->get(route('editor.editions.news-items.index', $edition));

        $response->assertForbidden();
    }

    public function test_user_without_editor_access_cannot_access_editor_workflow_routes(): void
    {
        $user = User::factory()->create();

        $edition = Edition::query()->create([
            'title' => 'Restricted Edition',
            'slug' => 'restricted-edition',
            'edition_type' => 'morning',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)->post(route('editor.editions.news-items.store', $edition), [
            'news_item_id' => $this->createNewsItem('item-three', 'Item Three')->id,
            'included_in_script' => true,
        ]);

        $response->assertForbidden();
    }

    public function test_locale_can_be_changed_to_spanish(): void
    {
        $editor = $this->createEditor();

        $response = $this->actingAs($editor)->post(route('locale.update'), [
            'locale' => 'es',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'es');
    }

    public function test_invalid_locale_is_rejected(): void
    {
        $editor = $this->createEditor();

        $response = $this->from(route('editor.dashboard'))->actingAs($editor)->post(route('locale.update'), [
            'locale' => 'fr',
        ]);

        $response->assertRedirect(route('editor.dashboard'));
        $response->assertSessionHasErrors('locale');
    }

    private function createEditor(): User
    {
        $editor = User::factory()->create();
        $editor->assignRole('editor');

        return $editor;
    }

    private function createNewsItem(string $slug, string $title): NewsItem
    {
        return NewsItem::query()->create([
            'title' => $title,
            'slug' => $slug,
            'status' => 'draft',
        ]);
    }
}
