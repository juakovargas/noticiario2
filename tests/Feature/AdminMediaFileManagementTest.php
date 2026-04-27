<?php

namespace Tests\Feature;

use App\Models\MediaFile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class AdminMediaFileManagementTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_access_media_files_index(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access']);

        $this->actingAs($admin)
            ->get(route('admin.media-files.index'))
            ->assertOk();
    }

    public function test_editor_cannot_access_media_files_index(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);

        $this->actingAs($editor)
            ->get(route('admin.media-files.index'))
            ->assertForbidden();
    }

    public function test_viewer_cannot_access_media_files_index(): void
    {
        $viewer = $this->createUserWithPermissions(['viewer.access']);

        $this->actingAs($viewer)
            ->get(route('admin.media-files.index'))
            ->assertForbidden();
    }

    public function test_media_file_show_page_works(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access']);
        $uploader = User::factory()->create();

        $mediaFile = MediaFile::query()->create([
            'uploaded_by' => $uploader->id,
            'disk' => 'public',
            'filename' => 'sample.png',
            'original_name' => 'sample.png',
            'path' => 'media/image/2026/04/sample.png',
            'mime_type' => 'image/png',
            'extension' => 'png',
            'size_bytes' => 1234,
            'media_type' => 'image',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.media-files.show', $mediaFile))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('mediaFile.id', $mediaFile->id));
    }
}
