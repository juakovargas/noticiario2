<?php

namespace Tests\Feature;

use App\Models\Edition;
use App\Models\HomePageSetting;
use App\Models\Script;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class HomePageSettingsTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    public function test_admin_can_access_home_page_settings_and_editor_cannot(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access']);
        $editor = $this->createUserWithPermissions(['editor.access']);

        $this->actingAs($admin)->get(route('admin.home-page-settings.index'))->assertOk();
        $this->actingAs($editor)->get(route('admin.home-page-settings.index'))->assertForbidden();
    }

    public function test_admin_can_create_and_update_home_settings(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access']);

        $this->actingAs($admin)->post(route('admin.home-page-settings.store'), [
            'locale' => 'en',
            'title' => 'Digital automated news bulletins',
            'subtitle' => 'Subtitle',
            'description' => 'Description',
            'hero_badge' => 'Badge',
            'primary_button_label' => 'Login',
            'primary_button_url' => 'https://example.test/login',
            'secondary_button_label' => 'Coverage',
            'secondary_button_url' => 'https://example.test',
            'show_latest_noticiarios' => true,
            'latest_noticiarios_limit' => 6,
            'show_world_map_preview' => true,
            'show_platforms_section' => true,
            'platforms' => ['youtube_shorts'],
            'is_active' => true,
        ])->assertRedirect();

        $setting = HomePageSetting::query()->firstOrFail();

        $this->actingAs($admin)->put(route('admin.home-page-settings.update', $setting), [
            'locale' => 'en',
            'title' => 'Updated title',
            'subtitle' => 'Subtitle',
            'description' => 'Description',
            'hero_badge' => 'Badge',
            'primary_button_label' => 'Login',
            'primary_button_url' => 'https://example.test/login',
            'secondary_button_label' => 'Coverage',
            'secondary_button_url' => 'https://example.test',
            'show_latest_noticiarios' => true,
            'latest_noticiarios_limit' => 8,
            'show_world_map_preview' => true,
            'show_platforms_section' => true,
            'platforms' => ['youtube_shorts', 'tiktok'],
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('home_page_settings', ['id' => $setting->id, 'title' => 'Updated title']);
    }

    public function test_home_page_uses_locale_specific_and_fallback_settings(): void
    {
        HomePageSetting::query()->create(['locale' => 'en', 'title' => 'English title', 'is_active' => true]);
        HomePageSetting::query()->create(['locale' => 'es', 'title' => 'Título español', 'is_active' => true]);

        $this->get('/?locale=es')->assertOk();
        app()->setLocale('es');
        $this->get(route('home'))->assertSee('Título español');

        HomePageSetting::query()->where('locale', 'es')->delete();
        app()->setLocale('fr');
        $this->get(route('home'))->assertSee('English title');
    }

    public function test_home_page_shows_only_ready_or_published_and_not_archived_scripts(): void
    {
        HomePageSetting::query()->create(['locale' => 'en', 'title' => 'Home', 'is_active' => true]);
        $edition = Edition::factory()->create();

        Script::factory()->create(['edition_id' => $edition->id, 'title' => 'Draft hidden', 'status' => 'draft', 'production_status' => 'draft']);
        Script::factory()->create(['edition_id' => $edition->id, 'title' => 'Ready visible', 'status' => 'approved', 'production_status' => 'ready_to_publish']);
        Script::factory()->create(['edition_id' => $edition->id, 'title' => 'Archived hidden', 'status' => 'approved', 'production_status' => 'published', 'deleted_at' => now()]);

        $this->get(route('home'))
            ->assertSee('Ready visible')
            ->assertDontSee('Draft hidden')
            ->assertDontSee('Archived hidden');
    }
}
