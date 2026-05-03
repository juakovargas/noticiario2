<?php

namespace Tests\Unit;

use App\Models\MediaFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaFileModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_url_uses_disk_and_path_when_url_is_null(): void
    {
        Storage::fake('public');

        $mediaFile = MediaFile::query()->create([
            'disk' => 'public',
            'directory' => 'media/images/users/2',
            'filename' => 'avatar.jpg',
            'original_name' => 'avatar.jpg',
            'path' => 'media/images/users/2/avatar.jpg',
            'url' => null,
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size_bytes' => 100,
            'media_type' => 'image',
            'visibility' => 'public',
            'status' => 'active',
        ]);

        $this->assertSame('/storage/media/images/users/2/avatar.jpg', $mediaFile->public_url);
    }

    public function test_public_url_normalizes_legacy_public_paths(): void
    {
        $this->assertSame('/storage/avatars/avatar.jpg', MediaFile::publicDiskUrl('storage/app/public/avatars/avatar.jpg'));
        $this->assertSame('/storage/avatars/avatar.jpg', MediaFile::publicDiskUrl('/storage/avatars/avatar.jpg'));
    }
}
