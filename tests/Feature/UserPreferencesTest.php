<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class UserPreferencesTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    public function test_authenticated_user_can_update_preferred_locale_and_timezone(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'preferred_locale' => 'es',
            'timezone' => 'Europe/Madrid',
            'date_format' => 'locale_default',
            'time_format' => '24h',
        ])->assertRedirect(route('profile.edit'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'preferred_locale' => 'es',
            'timezone' => 'Europe/Madrid',
        ]);
    }

    public function test_changing_locale_persists_to_user_preferred_locale(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('locale.update'), [
            'locale' => 'es',
        ])->assertRedirect();

        $this->assertSame('es', $user->fresh()->preferred_locale);
    }

    public function test_invalid_locale_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'preferred_locale' => 'zz',
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('preferred_locale');
    }

    public function test_avatar_upload_validation_rejects_invalid_file_type(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => UploadedFile::fake()->create('avatar.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('avatar');
    }


    public function test_admin_can_see_preferred_locale_in_user_listing(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access', 'users.view']);
        $user = User::factory()->create(['preferred_locale' => 'es', 'timezone' => 'Europe/Madrid']);

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee($user->email)
            ->assertSee('es');
    }

    public function test_admin_can_update_user_preferred_locale(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access', 'users.update']);
        $user = User::factory()->create();

        $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => true,
            'roles' => [],
            'permissions' => [],
            'preferred_locale' => 'es',
            'timezone' => 'Europe/Madrid',
            'date_format' => 'locale_default',
            'time_format' => '24h',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'preferred_locale' => 'es']);
    }

    public function test_auth_shared_props_include_safe_preference_fields(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $editor->update(['preferred_locale' => 'es', 'timezone' => 'Europe/Madrid']);

        $this->actingAs($editor)->get(route('editor.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user.preferred_locale', 'es')
                ->where('auth.user.timezone', 'Europe/Madrid')
                ->has('auth.user.avatar_url')
                ->where('auth.user.initials', $editor->initials)
            );
    }
}
