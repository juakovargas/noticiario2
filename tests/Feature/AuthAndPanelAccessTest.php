<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class AuthAndPanelAccessTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    public function test_guest_users_are_redirected_from_panel_routes(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
        $this->get('/editor')->assertRedirect(route('login'));
        $this->get('/viewer')->assertRedirect(route('login'));
    }

    public function test_user_with_admin_access_can_access_admin_dashboard(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access', 'dashboard.view']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_user_without_admin_access_cannot_access_admin_dashboard(): void
    {
        $user = $this->createUserWithPermissions(['dashboard.view']);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_user_with_editor_access_can_access_editor_dashboard(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);

        $this->actingAs($editor)
            ->get(route('editor.dashboard'))
            ->assertOk();
    }

    public function test_user_without_editor_access_cannot_access_editor_dashboard(): void
    {
        $user = $this->createUserWithPermissions(['viewer.access', 'viewer.dashboard.view']);

        $this->actingAs($user)
            ->get(route('editor.dashboard'))
            ->assertForbidden();
    }

    public function test_user_with_viewer_access_can_access_viewer_dashboard(): void
    {
        $viewer = $this->createUserWithPermissions(['viewer.access', 'viewer.dashboard.view']);

        $this->actingAs($viewer)
            ->get(route('viewer.dashboard'))
            ->assertOk();
    }

    public function test_user_without_viewer_access_cannot_access_viewer_dashboard(): void
    {
        $user = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);

        $this->actingAs($user)
            ->get(route('viewer.dashboard'))
            ->assertForbidden();
    }

    public function test_editor_cannot_access_admin_without_admin_access(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);

        $this->actingAs($editor)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_viewer_cannot_access_admin_or_editor(): void
    {
        $viewer = $this->createUserWithPermissions(['viewer.access', 'viewer.dashboard.view']);

        $this->actingAs($viewer)
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->get(route('editor.dashboard'))
            ->assertForbidden();
    }
}
