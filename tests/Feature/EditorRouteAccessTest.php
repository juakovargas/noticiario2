<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class EditorRouteAccessTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    public function test_editor_can_access_main_editor_indexes(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);

        $this->actingAs($editor)->get(route('editor.locations.index'))->assertOk();
        $this->actingAs($editor)->get(route('editor.news-categories.index'))->assertOk();
        $this->actingAs($editor)->get(route('editor.news-sources.index'))->assertOk();
        $this->actingAs($editor)->get(route('editor.news-items.index'))->assertOk();
        $this->actingAs($editor)->get(route('editor.editions.index'))->assertOk();
        $this->actingAs($editor)->get(route('editor.scripts.index'))->assertOk();
    }

    public function test_viewer_cannot_access_main_editor_indexes(): void
    {
        $viewer = $this->createUserWithPermissions(['viewer.access', 'viewer.dashboard.view']);

        $this->actingAs($viewer)->get(route('editor.locations.index'))->assertForbidden();
        $this->actingAs($viewer)->get(route('editor.news-categories.index'))->assertForbidden();
        $this->actingAs($viewer)->get(route('editor.news-sources.index'))->assertForbidden();
        $this->actingAs($viewer)->get(route('editor.news-items.index'))->assertForbidden();
        $this->actingAs($viewer)->get(route('editor.editions.index'))->assertForbidden();
        $this->actingAs($viewer)->get(route('editor.scripts.index'))->assertForbidden();
    }
}
