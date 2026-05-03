<?php

namespace Tests\Unit;

use App\Models\MediaFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserAvatarResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_avatar_url_prefers_profile_image_media_file(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['avatar_path' => 'avatars/users/legacy.png']);
        Storage::disk('public')->put('media/images/users/1/profile.png', 'content');

        $mediaFile = MediaFile::query()->create([
            'uploaded_by' => $user->id,
            'disk' => 'public',
            'directory' => 'media/images/users/1',
            'filename' => 'profile.png',
            'original_name' => 'profile.png',
            'path' => 'media/images/users/1/profile.png',
            'mime_type' => 'image/png',
            'extension' => 'png',
            'size_bytes' => 10,
            'media_type' => 'image',
            'collection' => 'profile_images',
            'visibility' => 'public',
            'status' => 'active',
        ]);

        $user->update(['profile_image_id' => $mediaFile->id]);

        $this->assertSame('/storage/media/images/users/1/profile.png', $user->fresh()->avatar_url);
    }

    public function test_user_avatar_url_falls_back_to_avatar_path_when_profile_image_missing(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/users/2/legacy.png', 'legacy');

        $user = User::factory()->create([
            'avatar_path' => 'avatars/users/2/legacy.png',
            'profile_image_id' => null,
        ]);

        $this->assertSame('/storage/avatars/users/2/legacy.png', $user->avatar_url);
    }

    public function test_user_avatar_url_normalizes_legacy_storage_path(): void
    {
        $user = User::factory()->create([
            'avatar_path' => 'storage/app/public/avatars/legacy.png',
            'profile_image_id' => null,
        ]);

        $this->assertSame('/storage/avatars/legacy.png', $user->avatar_url);
    }

    public function test_user_initials_are_generated_from_name(): void
    {
        $user = User::factory()->make(['name' => 'Editor User']);

        $this->assertSame('EU', $user->initials);
        $this->assertSame('U', User::factory()->make(['name' => '  '])->initials);
    }
}
