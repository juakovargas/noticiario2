<?php

namespace Database\Seeders;

use App\Models\SeoSetting;
use Illuminate\Database\Seeder;

class SeoSettingSeeder extends Seeder
{
    public function run(): void
    {
        SeoSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'site_name' => 'Noticiario',
                'default_title' => 'Noticiario',
                'title_suffix' => 'Noticiario',
                'default_description' => 'Editorial automation platform for short digital news bulletins.',
                'default_robots' => 'index,follow',
                'default_twitter_card' => 'summary_large_image',
                'enable_tracking' => false,
                'enable_custom_scripts' => false,
                'enable_indexing' => true,
            ],
        );
    }
}
