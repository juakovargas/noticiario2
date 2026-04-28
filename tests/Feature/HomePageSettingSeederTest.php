<?php

namespace Tests\Feature;

use Database\Seeders\HomePageSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HomePageSettingSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_settings_exist_for_en_es_fr_and_no_duplicates(): void
    {
        $this->seed(HomePageSettingSeeder::class);
        $this->seed(HomePageSettingSeeder::class);

        $locales = DB::table('home_page_settings')->pluck('locale')->all();

        $this->assertContains('en', $locales);
        $this->assertContains('es', $locales);
        $this->assertContains('fr', $locales);
        $this->assertSame(3, DB::table('home_page_settings')->count());
    }
}
