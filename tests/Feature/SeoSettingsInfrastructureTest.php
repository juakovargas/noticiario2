<?php

namespace Tests\Feature;

use App\Models\SeoSetting;
use Database\Seeders\SeoSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SeoSettingsInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_seo_settings_default_row_exists_after_seeding(): void
    {
        $this->seed(SeoSettingSeeder::class);

        $this->assertDatabaseHas('seo_settings', [
            'site_name' => 'Noticiario',
            'enable_tracking' => false,
            'enable_custom_scripts' => false,
            'enable_indexing' => true,
        ]);
    }

    public function test_running_seeder_twice_does_not_duplicate_seo_settings(): void
    {
        $this->seed(SeoSettingSeeder::class);
        $this->seed(SeoSettingSeeder::class);

        $this->assertSame(1, SeoSetting::query()->count());
    }

    public function test_root_view_does_not_crash_when_seo_settings_table_is_missing(): void
    {
        Schema::drop('seo_settings');

        $this->get('/')
            ->assertOk();
    }

    public function test_root_view_injects_noindex_when_indexing_disabled(): void
    {
        SeoSetting::current()->update([
            'enable_indexing' => false,
            'default_robots' => 'index,follow',
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('name="robots" content="noindex,nofollow"', false);
    }

    public function test_root_view_injects_verification_tag_when_configured(): void
    {
        SeoSetting::current()->update([
            'google_site_verification' => 'verify-token-123',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('name="google-site-verification" content="verify-token-123"', false);
    }

    public function test_tracking_scripts_are_not_injected_when_tracking_disabled(): void
    {
        SeoSetting::current()->update([
            'enable_tracking' => false,
            'google_tag_manager_id' => 'GTM-123456',
            'google_analytics_id' => 'G-123456789',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('www.googletagmanager.com/gtm.js', false)
            ->assertDontSee('www.googletagmanager.com/gtag/js', false);
    }

    public function test_custom_scripts_are_not_injected_when_custom_scripts_disabled(): void
    {
        SeoSetting::current()->update([
            'enable_custom_scripts' => false,
            'custom_head_scripts' => '<script>window.customHead=true;</script>',
            'custom_body_start_scripts' => '<div id="body-start">start</div>',
            'custom_body_end_scripts' => '<div id="body-end">end</div>',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('window.customHead=true', false)
            ->assertDontSee('id="body-start"', false)
            ->assertDontSee('id="body-end"', false);
    }
}
