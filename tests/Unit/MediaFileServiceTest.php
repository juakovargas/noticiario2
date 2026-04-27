<?php

namespace Tests\Unit;

use App\Models\MediaFile;
use App\Models\User;
use App\Services\Media\MediaFileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaFileServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_uploaded_image_and_media_metadata(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $service = app(MediaFileService::class);

        $mediaFile = $service->storeUploadedFile(
            UploadedFile::fake()->image('profile-photo.png', 220, 120),
            $user,
            ['collection' => 'profile_images']
        );

        $this->assertDatabaseHas('media_files', [
            'id' => $mediaFile->id,
            'uploaded_by' => $user->id,
            'original_name' => 'profile-photo.png',
            'media_type' => 'image',
            'collection' => 'profile_images',
        ]);

        $this->assertNotNull($mediaFile->mime_type);
        $this->assertSame('png', $mediaFile->extension);
        $this->assertGreaterThan(0, $mediaFile->size_bytes);
        $this->assertNotNull($mediaFile->width);
        $this->assertNotNull($mediaFile->height);
        Storage::disk('public')->assertExists($mediaFile->path);
    }

    public function test_it_replaces_user_profile_image_and_archives_previous_file(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $service = app(MediaFileService::class);

        $first = $service->replaceUserProfileImage($user, UploadedFile::fake()->image('first.jpg', 120, 120), $user);
        $second = $service->replaceUserProfileImage($user->fresh(), UploadedFile::fake()->image('second.jpg', 120, 120), $user);

        $this->assertSame($second->id, $user->fresh()->profile_image_id);
        $this->assertSame('archived', MediaFile::query()->findOrFail($first->id)->status);
        $this->assertSame('active', MediaFile::query()->findOrFail($second->id)->status);
    }
}
