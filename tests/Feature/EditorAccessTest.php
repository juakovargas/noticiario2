<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditorAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_editor_user_can_access_editor_dashboard(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('editor');

        $response = $this->actingAs($editor)->get(route('editor.dashboard'));

        $response->assertOk();
    }

    public function test_editor_user_can_access_locations_index(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('editor');

        $response = $this->actingAs($editor)->get(route('editor.locations.index'));

        $response->assertOk();
    }

    public function test_viewer_user_cannot_access_editor_locations(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('viewer');

        $response = $this->actingAs($viewer)->get(route('editor.locations.index'));

        $response->assertForbidden();
    }

    public function test_user_without_editor_access_cannot_access_editor_routes(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('editor.locations.index'));

        $response->assertForbidden();
    }
}
