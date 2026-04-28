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

        $this->assertSame(Storage::disk('public')->url('media/images/users/2/avatar.jpg'), $mediaFile->public_url);
    }
}
