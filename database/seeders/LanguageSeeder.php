<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $appDefault = config('app.locale', 'en');
        $defaultExists = Language::query()->where('is_default', true)->exists();

        $englishDefault = ! $defaultExists && $appDefault === 'en';
        $spanishDefault = ! $defaultExists && $appDefault === 'es';

        $english = Language::query()->updateOrCreate(
            ['code' => 'en'],
            [
                'name' => 'English',
                'native_name' => 'English',
                'flag_emoji' => '🇬🇧',
                'is_active' => true,
                'is_default' => $defaultExists ? (bool) Language::query()->where('code', 'en')->value('is_default') : $englishDefault,
                'sort_order' => 1,
            ],
        );

        $spanish = Language::query()->updateOrCreate(
            ['code' => 'es'],
            [
                'name' => 'Spanish',
                'native_name' => 'Español',
                'flag_emoji' => '🇪🇸',
                'is_active' => true,
                'is_default' => $defaultExists ? (bool) Language::query()->where('code', 'es')->value('is_default') : $spanishDefault,
                'sort_order' => 2,
            ],
        );

        if (! Language::query()->where('is_default', true)->exists()) {
            $english->update(['is_default' => true]);
            $spanish->update(['is_default' => false]);
        }
    }
}
