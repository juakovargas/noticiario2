<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_impersonate_editor_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $editor = User::factory()->create();
        $editor->assignRole('editor');

        $response = $this->actingAs($admin)
            ->post(route('admin.users.impersonate', $editor));

        $response->assertRedirect(route('editor.dashboard'));
        $this->assertAuthenticatedAs($editor);
        $this->assertEquals($admin->id, session('impersonator_id'));
    }

    public function test_admin_can_stop_impersonation_and_return_to_original_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $viewer = User::factory()->create();
        $viewer->assignRole('viewer');

        $this->actingAs($admin)->post(route('admin.users.impersonate', $viewer));

        $response = $this->actingAs($viewer)
            ->post(route('admin.impersonation.stop'));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertAuthenticatedAs($admin);
        $this->assertNull(session('impersonator_id'));
    }

    public function test_editor_cannot_impersonate_another_user(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('editor');

        $target = User::factory()->create();
        $target->assignRole('viewer');

        $this->actingAs($editor)
            ->post(route('admin.users.impersonate', $target))
            ->assertForbidden();
    }

    public function test_viewer_cannot_impersonate_another_user(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('viewer');

        $target = User::factory()->create();
        $target->assignRole('editor');

        $this->actingAs($viewer)
            ->post(route('admin.users.impersonate', $target))
            ->assertForbidden();
    }

    public function test_guest_cannot_impersonate(): void
    {
        $target = User::factory()->create();

        $this->post(route('admin.users.impersonate', $target))
            ->assertRedirect(route('login'));
    }

    public function test_admin_cannot_impersonate_themselves(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('admin.users.impersonate', $admin))
            ->assertRedirect();

        $this->assertAuthenticatedAs($admin);
        $this->assertNull(session('impersonator_id'));
    }

    public function test_nested_impersonation_is_rejected(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $secondAdmin = User::factory()->create();
        $secondAdmin->assignRole('admin');

        $viewer = User::factory()->create();
        $viewer->assignRole('viewer');

        $this->actingAs($admin)->post(route('admin.users.impersonate', $secondAdmin));

        $this->actingAs($secondAdmin)
            ->post(route('admin.users.impersonate', $viewer))
            ->assertRedirect();

        $this->assertAuthenticatedAs($secondAdmin);
        $this->assertEquals($admin->id, session('impersonator_id'));
    }
}
