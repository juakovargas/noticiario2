<?php

namespace Tests\Feature;

use App\Models\SeoSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class AdminSeoSettingsTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    public function test_admin_can_access_seo_settings_edit_page(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access']);

        $this->actingAs($admin)
            ->get(route('admin.seo-settings.edit'))
            ->assertOk();
    }

    public function test_editor_cannot_access_seo_settings_edit_page(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);

        $this->actingAs($editor)
            ->get(route('admin.seo-settings.edit'))
            ->assertForbidden();
    }

    public function test_viewer_cannot_access_seo_settings_edit_page(): void
    {
        $viewer = $this->createUserWithPermissions(['viewer.access', 'viewer.dashboard.view']);

        $this->actingAs($viewer)
            ->get(route('admin.seo-settings.edit'))
            ->assertForbidden();
    }

    public function test_guest_cannot_access_seo_settings_edit_page(): void
    {
        $this->get(route('admin.seo-settings.edit'))->assertRedirect(route('login'));
    }

    public function test_admin_can_update_seo_settings(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access']);

        $this->actingAs($admin)
            ->put(route('admin.seo-settings.update'), [
                'site_name' => 'Noticiario Pro',
                'default_title' => 'Noticiario Daily',
                'title_suffix' => 'Noticiario',
                'default_description' => 'Description',
                'default_keywords' => 'news,editorial',
                'canonical_base_url' => 'https://example.com',
                'default_robots' => 'index,follow',
                'google_site_verification' => 'google-token',
                'google_tag_manager_id' => 'GTM-1234567',
                'enable_tracking' => true,
                'enable_custom_scripts' => true,
                'enable_indexing' => false,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('seo_settings', [
            'site_name' => 'Noticiario Pro',
            'google_site_verification' => 'google-token',
            'google_tag_manager_id' => 'GTM-1234567',
            'enable_tracking' => true,
            'enable_custom_scripts' => true,
            'enable_indexing' => false,
        ]);
    }

    public function test_enable_tracking_and_custom_scripts_can_be_disabled(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access']);
        SeoSetting::current()->update([
            'enable_tracking' => true,
            'enable_custom_scripts' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.seo-settings.update'), [
                'default_robots' => 'index,follow',
                'enable_tracking' => false,
                'enable_custom_scripts' => false,
                'enable_indexing' => true,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('seo_settings', [
            'enable_tracking' => false,
            'enable_custom_scripts' => false,
        ]);
    }

    public function test_invalid_canonical_base_url_is_rejected(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access']);

        $this->actingAs($admin)
            ->put(route('admin.seo-settings.update'), [
                'default_robots' => 'index,follow',
                'canonical_base_url' => 'not-a-url',
            ])
            ->assertSessionHasErrors('canonical_base_url');
    }
}
