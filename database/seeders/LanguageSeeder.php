<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $appDefault = config('app.locale', 'en');

        $existingDefaultCode = Language::query()->where('is_default', true)->value('code');
        $defaultCode = $existingDefaultCode ?: (in_array($appDefault, ['en', 'es', 'fr'], true) ? $appDefault : 'en');

        $definitions = [
            ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'flag_emoji' => '🇬🇧', 'sort_order' => 1],
            ['code' => 'es', 'name' => 'Spanish', 'native_name' => 'Español', 'flag_emoji' => '🇪🇸', 'sort_order' => 2],
            ['code' => 'fr', 'name' => 'French', 'native_name' => 'Français', 'flag_emoji' => '🇫🇷', 'sort_order' => 3],
        ];

        foreach ($definitions as $definition) {
            Language::query()->updateOrCreate(
                ['code' => $definition['code']],
                [
                    'name' => $definition['name'],
                    'native_name' => $definition['native_name'],
                    'flag_emoji' => $definition['flag_emoji'],
                    'is_active' => true,
                    'is_default' => $definition['code'] === $defaultCode,
                    'sort_order' => $definition['sort_order'],
                ],
            );
        }

        $activeDefault = Language::query()->where('is_default', true)->orderBy('id')->first();
        if ($activeDefault) {
            Language::query()->whereKeyNot($activeDefault->id)->update(['is_default' => false]);
        }
    }
}
