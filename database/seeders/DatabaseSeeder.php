<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            LanguageSeeder::class,
            SeoSettingSeeder::class,
            AiProviderSeeder::class,
            HomePageSettingSeeder::class,
        ]);

        if (app()->environment(['local', 'testing'])) {
            $this->call([
                DemoUsersSeeder::class,
                DemoEditorialSeeder::class,
            ]);
        }
    }
}
