<?php

namespace Tests\Feature;

use App\Models\MediaFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_user_can_upload_profile_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/profile', [
                '_method' => 'patch',
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => UploadedFile::fake()->image('avatar.png', 180, 180),
            ])
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertNotNull($user->profile_image_id);
        $this->assertDatabaseHas('media_files', ['id' => $user->profile_image_id, 'media_type' => 'image']);
    }

    public function test_profile_update_validates_name_and_email_with_avatar_payload(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile')
            ->post('/profile', [
                '_method' => 'patch',
                'name' => '',
                'email' => '',
                'avatar' => UploadedFile::fake()->image('avatar.png'),
            ])
            ->assertRedirect('/profile')
            ->assertSessionHasErrors(['name', 'email']);
    }

    public function test_profile_update_without_avatar_still_works(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => 'Renamed User',
                'email' => $user->email,
            ])
            ->assertRedirect('/profile')
            ->assertSessionHasNoErrors();

        $this->assertSame('Renamed User', $user->fresh()->name);
    }

    public function test_profile_upload_rejects_invalid_file_type(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile')
            ->post('/profile', [
                '_method' => 'patch',
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => UploadedFile::fake()->create('avatar.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect('/profile')
            ->assertSessionHasErrors('avatar');
    }

    public function test_profile_upload_rejects_oversized_file(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile')
            ->post('/profile', [
                '_method' => 'patch',
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => UploadedFile::fake()->image('large.png')->size(2500),
            ])
            ->assertRedirect('/profile')
            ->assertSessionHasErrors('avatar');
    }

    public function test_auth_shared_props_include_avatar_url_with_legacy_avatar_path_fallback(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/users/1/legacy.png', 'legacy-avatar-content');

        $user = User::factory()->create([
            'avatar_path' => 'avatars/users/1/legacy.png',
            'profile_image_id' => null,
        ]);

        $this->actingAs($user)
            ->get('/profile')
            ->assertInertia(fn ($page) => $page->where('auth.user.avatar_url', Storage::disk('public')->url('avatars/users/1/legacy.png')));
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
