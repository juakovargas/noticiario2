<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_access_user_edit_page(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access', 'users.update']);
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $user))
            ->assertOk();
    }

    public function test_editor_cannot_access_user_edit_page(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);
        $user = User::factory()->create();

        $this->actingAs($editor)
            ->get(route('admin.users.edit', $user))
            ->assertForbidden();
    }

    public function test_viewer_cannot_access_user_edit_page(): void
    {
        $viewer = $this->createUserWithPermissions(['viewer.access']);
        $user = User::factory()->create();

        $this->actingAs($viewer)
            ->get(route('admin.users.edit', $user))
            ->assertForbidden();
    }

    public function test_guest_cannot_access_user_edit_page(): void
    {
        $user = User::factory()->create();

        $this->get(route('admin.users.edit', $user))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_update_user_name_and_email(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access', 'users.update']);
        $target = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.com']);

        $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'is_active' => true,
            'roles' => ['viewer'],
            'permissions' => [],
            'preferred_locale' => 'en',
            'timezone' => 'UTC',
            'date_format' => 'locale_default',
            'time_format' => '24h',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);
    }

    public function test_admin_can_update_user_roles(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['admin']);
        $admin->givePermissionTo(['admin.access', 'users.update']);

        $target = User::factory()->create();
        $target->syncRoles(['viewer']);

        $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'is_active' => true,
            'roles' => ['editor'],
            'permissions' => [],
            'preferred_locale' => 'en',
            'timezone' => 'UTC',
            'date_format' => 'locale_default',
            'time_format' => '24h',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertTrue($target->fresh()->hasRole('editor'));
        $this->assertFalse($target->fresh()->hasRole('viewer'));
    }

    public function test_admin_can_update_preferred_locale_and_timezone(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access', 'users.update']);
        $target = User::factory()->create(['preferred_locale' => 'en', 'timezone' => 'UTC']);

        $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'is_active' => true,
            'roles' => ['viewer'],
            'permissions' => [],
            'preferred_locale' => 'es',
            'timezone' => 'Europe/Madrid',
            'date_format' => 'locale_default',
            'time_format' => '24h',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'preferred_locale' => 'es',
            'timezone' => 'Europe/Madrid',
        ]);
    }

    public function test_admin_update_works_with_method_spoofed_multipart_payload(): void
    {
        Storage::fake('public');
        $admin = $this->createUserWithPermissions(['admin.access', 'users.update']);
        $target = User::factory()->create(['email' => 'target@example.com']);

        $this->actingAs($admin)->post(route('admin.users.update', $target), [
            '_method' => 'put',
            'name' => 'Updated Target',
            'email' => 'target@example.com',
            'is_active' => true,
            'roles' => ['viewer'],
            'permissions' => [],
            'preferred_locale' => 'es',
            'timezone' => 'Europe/Madrid',
            'date_format' => 'locale_default',
            'time_format' => '24h',
            'avatar' => UploadedFile::fake()->image('target.png'),
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['id' => $target->id, 'name' => 'Updated Target']);
        $this->assertNotNull($target->fresh()->avatar_path);
    }

    public function test_roles_with_permissions_are_shared_to_edit_page(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access', 'users.update']);
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $user))
            ->assertInertia(fn ($page) => $page->has('rolesWithPermissions'));
    }

    public function test_update_fails_when_name_is_missing(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access', 'users.update']);
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.users.edit', $target))
            ->put(route('admin.users.update', $target), [
                'name' => '',
                'email' => $target->email,
                'is_active' => true,
                'roles' => ['viewer'],
                'permissions' => [],
            ])
            ->assertRedirect(route('admin.users.edit', $target))
            ->assertSessionHasErrors('name');
    }

    public function test_update_fails_when_email_is_missing(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access', 'users.update']);
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.users.edit', $target))
            ->put(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => '',
                'is_active' => true,
                'roles' => ['viewer'],
                'permissions' => [],
            ])
            ->assertRedirect(route('admin.users.edit', $target))
            ->assertSessionHasErrors('email');
    }

    public function test_update_fails_when_email_is_invalid(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access', 'users.update']);
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.users.edit', $target))
            ->put(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => 'invalid-email',
                'is_active' => true,
                'roles' => ['viewer'],
                'permissions' => [],
            ])
            ->assertRedirect(route('admin.users.edit', $target))
            ->assertSessionHasErrors('email');
    }

    public function test_update_ignores_unique_email_rule_for_current_user(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access', 'users.update']);
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'is_active' => true,
                'roles' => ['viewer'],
                'permissions' => [],
                'preferred_locale' => 'en',
                'timezone' => 'UTC',
                'date_format' => 'locale_default',
                'time_format' => '24h',
            ])
            ->assertRedirect(route('admin.users.index'));
    }

    public function test_update_rejects_duplicate_email_from_another_user(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access', 'users.update']);
        $target = User::factory()->create();
        $other = User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($admin)
            ->from(route('admin.users.edit', $target))
            ->put(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => $other->email,
                'is_active' => true,
                'roles' => ['viewer'],
                'permissions' => [],
            ])
            ->assertRedirect(route('admin.users.edit', $target))
            ->assertSessionHasErrors('email');
    }

    public function test_cannot_remove_last_admin_like_access_from_only_admin_user(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['admin']);
        $admin->givePermissionTo(['admin.access', 'users.update']);

        $this->actingAs($admin)
            ->from(route('admin.users.edit', $admin))
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'is_active' => true,
                'roles' => ['viewer'],
                'permissions' => [],
                'preferred_locale' => 'en',
                'timezone' => 'UTC',
                'date_format' => 'locale_default',
                'time_format' => '24h',
            ])
            ->assertRedirect(route('admin.users.edit', $admin));

        $this->assertTrue($admin->fresh()->hasRole('admin'));
    }
}
