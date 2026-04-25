<?php

namespace Tests\Feature;

use App\Models\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class AdminLanguageManagementTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    public function test_admin_can_access_languages_index(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access', 'dashboard.view']);

        $this->actingAs($admin)->get(route('admin.languages.index'))->assertOk();
    }

    public function test_editor_cannot_access_admin_languages(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);

        $this->actingAs($editor)->get(route('admin.languages.index'))->assertForbidden();
    }

    public function test_admin_can_create_a_language(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access', 'dashboard.view']);

        $this->actingAs($admin)->post(route('admin.languages.store'), [
            'name' => 'French',
            'native_name' => 'Français',
            'code' => 'fr',
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 3,
        ])->assertRedirect(route('admin.languages.index'));

        $this->assertDatabaseHas('languages', ['code' => 'fr']);
    }

    public function test_setting_default_language_unsets_previous_default(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access', 'dashboard.view']);

        $english = Language::factory()->create(['code' => 'en', 'is_default' => true]);
        $spanish = Language::factory()->create(['code' => 'es', 'is_default' => false]);

        $this->actingAs($admin)->put(route('admin.languages.update', $spanish), [
            'name' => $spanish->name,
            'native_name' => $spanish->native_name,
            'code' => 'es',
            'flag_emoji' => '🇪🇸',
            'is_active' => true,
            'is_default' => true,
            'sort_order' => 1,
        ])->assertRedirect(route('admin.languages.index'));

        $english->refresh();
        $spanish->refresh();

        $this->assertFalse($english->is_default);
        $this->assertTrue($spanish->is_default);
    }

    public function test_non_admin_cannot_manage_languages(): void
    {
        $viewer = $this->createUserWithPermissions(['viewer.access', 'viewer.dashboard.view']);

        $this->actingAs($viewer)->post(route('admin.languages.store'), [
            'name' => 'French',
            'code' => 'fr',
        ])->assertForbidden();
    }
}
