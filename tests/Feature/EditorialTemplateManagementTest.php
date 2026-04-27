<?php

namespace Tests\Feature;

use App\Models\EditorialTemplate;
use App\Models\Language;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class EditorialTemplateManagementTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    public function test_editor_can_access_editorial_templates_index(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);

        $this->actingAs($editor)->get(route('editor.editorial-templates.index'))->assertOk();
    }

    public function test_viewer_cannot_access_editorial_templates(): void
    {
        $viewer = $this->createUserWithPermissions(['viewer.access', 'viewer.dashboard.view']);

        $this->actingAs($viewer)->get(route('editor.editorial-templates.index'))->assertForbidden();
    }

    public function test_editor_can_create_update_and_soft_delete_editorial_template(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $language = Language::factory()->create(['code' => 'es']);
        $location = Location::factory()->create();

        $this->actingAs($editor)->post(route('editor.editorial-templates.store'), [
            'name' => 'My Template',
            'slug' => '',
            'language_id' => $language->id,
            'location_id' => $location->id,
            'edition_type' => 'morning',
            'target_duration_seconds' => 90,
            'is_active' => true,
        ])->assertRedirect(route('editor.editorial-templates.index'));

        $template = EditorialTemplate::query()->firstOrFail();
        $this->assertSame('my-template', $template->slug);

        $this->actingAs($editor)->put(route('editor.editorial-templates.update', $template), [
            'name' => 'My Updated Template',
            'slug' => '',
            'language_id' => $language->id,
            'location_id' => $location->id,
            'edition_type' => 'night',
            'target_duration_seconds' => 120,
            'is_active' => true,
            'sort_order' => 1,
        ])->assertRedirect(route('editor.editorial-templates.index'));

        $this->assertDatabaseHas('editorial_templates', ['id' => $template->id, 'name' => 'My Updated Template']);

        $this->actingAs($editor)->delete(route('editor.editorial-templates.destroy', $template))->assertRedirect(route('editor.editorial-templates.index'));

        $this->assertSoftDeleted('editorial_templates', ['id' => $template->id]);
    }
}
