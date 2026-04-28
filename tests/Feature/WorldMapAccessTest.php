<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class WorldMapAccessTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    public function test_admin_can_access_admin_world_map(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access']);

        $this->actingAs($admin)->get(route('admin.world-map.index'))->assertOk();
    }

    public function test_editor_cannot_access_admin_world_map(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);

        $this->actingAs($editor)->get(route('admin.world-map.index'))->assertForbidden();
    }

    public function test_viewer_cannot_access_admin_or_editor_world_map(): void
    {
        $viewer = $this->createUserWithPermissions(['viewer.access']);

        $this->actingAs($viewer)->get(route('admin.world-map.index'))->assertForbidden();
        $this->actingAs($viewer)->get(route('editor.world-map.index'))->assertForbidden();
    }

    public function test_editor_can_access_editor_world_map(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);

        $this->actingAs($editor)->get(route('editor.world-map.index'))->assertOk();
    }

    public function test_viewer_can_access_viewer_world_map(): void
    {
        $viewer = $this->createUserWithPermissions(['viewer.access']);

        $this->actingAs($viewer)->get(route('viewer.world-map.index'))->assertOk();
    }
}
