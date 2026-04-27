<?php

namespace Tests\Feature;

use App\Models\Edition;
use App\Models\EditorialTemplate;
use App\Models\Language;
use App\Models\Location;
use App\Models\NewsCategory;
use App\Models\NewsItem;
use App\Models\NewsSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class ScriptBuilderTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    public function test_editor_can_access_script_builder_for_an_edition(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $edition = Edition::factory()->create();

        $this->actingAs($editor)
            ->get(route('editor.editions.script-builder', $edition))
            ->assertOk();
    }

    public function test_viewer_cannot_access_script_builder(): void
    {
        $viewer = $this->createUserWithPermissions(['viewer.access', 'viewer.dashboard.view']);
        $edition = Edition::factory()->create();

        $this->actingAs($viewer)
            ->get(route('editor.editions.script-builder', $edition))
            ->assertForbidden();
    }

    public function test_user_without_editor_access_cannot_access_script_builder(): void
    {
        $user = $this->createUserWithPermissions([]);
        $edition = Edition::factory()->create();

        $this->actingAs($user)
            ->get(route('editor.editions.script-builder', $edition))
            ->assertForbidden();
    }

    public function test_script_builder_creates_draft_script_with_metadata_and_only_included_news_items(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);

        $location = Location::factory()->create();
        $category = NewsCategory::factory()->create();
        $source = NewsSource::factory()->create();

        $edition = Edition::factory()->create(['location_id' => $location->id, 'language' => 'es']);
        $included = NewsItem::factory()->create(['location_id' => $location->id, 'news_category_id' => $category->id, 'news_source_id' => $source->id]);
        $excluded = NewsItem::factory()->create(['location_id' => $location->id, 'news_category_id' => $category->id, 'news_source_id' => $source->id]);

        $edition->newsItems()->attach($included->id, ['sort_order' => 1, 'included_in_script' => true]);
        $edition->newsItems()->attach($excluded->id, ['sort_order' => 2, 'included_in_script' => false]);

        $this->actingAs($editor)
            ->post(route('editor.editions.script-builder.store', $edition), [
                'title' => 'Manual Script',
                'status' => 'draft',
                'language' => '',
                'intro' => 'Intro',
                'body' => 'Body',
                'outro' => 'Outro',
                'estimated_duration_seconds' => 120,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('scripts', [
            'edition_id' => $edition->id,
            'title' => 'Manual Script',
            'status' => 'draft',
            'language' => 'es',
        ]);

        $script = $edition->scripts()->latest('id')->firstOrFail();

        $this->assertSame('manual_builder', $script->metadata['created_from']);
        $this->assertSame([$included->id], $script->metadata['news_item_ids']);
    }

    public function test_script_builder_stores_selected_editorial_template_metadata(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);

        $language = Language::factory()->create(['code' => 'es']);
        $location = Location::factory()->create(['default_language_id' => $language->id]);
        $edition = Edition::factory()->create(['location_id' => $location->id, 'language' => 'es']);

        $template = EditorialTemplate::query()->create([
            'name' => 'Template One',
            'slug' => 'template-one',
            'language_id' => $language->id,
            'location_id' => $location->id,
            'is_active' => true,
        ]);

        $this->actingAs($editor)
            ->post(route('editor.editions.script-builder.store', $edition), [
                'title' => 'Template Script',
                'status' => 'draft',
                'language' => '',
                'editorial_template_id' => $template->id,
                'intro' => 'Intro',
                'body' => 'Body',
                'outro' => 'Outro',
            ])
            ->assertRedirect();

        $script = $edition->scripts()->latest('id')->firstOrFail();

        $this->assertSame($template->id, $script->metadata['editorial_template_id']);
    }

}
