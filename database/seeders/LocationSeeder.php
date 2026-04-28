<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $english = Language::query()->where('code', 'en')->first();
        $spanish = Language::query()->where('code', 'es')->first();
        $french = Language::query()->firstOrCreate(
            ['code' => 'fr'],
            [
                'name' => 'French',
                'native_name' => 'Français',
                'flag_emoji' => '🇫🇷',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 3,
            ],
        );

        $global = Location::query()->updateOrCreate(
            ['slug' => 'global'],
            ['name' => 'Global', 'type' => 'global', 'is_active' => true, 'sort_order' => 0, 'default_language_id' => $english?->id, 'latitude' => 20.0000000, 'longitude' => 0.0000000, 'map_zoom' => 2, 'show_on_map' => true]
        );

        $spain = Location::query()->updateOrCreate(
            ['slug' => 'spain'],
            ['parent_id' => $global->id, 'name' => 'Spain', 'type' => 'country', 'country_code' => 'ES', 'is_active' => true, 'sort_order' => 1, 'default_language_id' => $spanish?->id, 'latitude' => 40.4167750, 'longitude' => -3.7037900, 'map_zoom' => 5, 'show_on_map' => true]
        );

        foreach (['madrid' => 'Madrid', 'barcelona' => 'Barcelona', 'valencia' => 'Valencia', 'sevilla' => 'Sevilla'] as $slug => $name) {
            Location::query()->updateOrCreate(
                ['slug' => $slug],
                ['parent_id' => $spain->id, 'name' => $name, 'type' => 'city', 'country_code' => 'ES', 'is_active' => true, 'default_language_id' => $spanish?->id, 'show_on_map' => $slug === 'madrid', 'latitude' => $slug === 'madrid' ? 40.4167750 : null, 'longitude' => $slug === 'madrid' ? -3.7037900 : null, 'map_zoom' => $slug === 'madrid' ? 8 : null]
            );
        }

        $france = Location::query()->updateOrCreate(
            ['slug' => 'france'],
            ['parent_id' => $global->id, 'name' => 'France', 'type' => 'country', 'country_code' => 'FR', 'is_active' => true, 'sort_order' => 2, 'default_language_id' => $french->id, 'latitude' => 46.2276380, 'longitude' => 2.2137490, 'map_zoom' => 5, 'show_on_map' => true]
        );

        foreach (['paris' => 'Paris', 'lyon' => 'Lyon'] as $slug => $name) {
            Location::query()->updateOrCreate(
                ['slug' => $slug],
                ['parent_id' => $france->id, 'name' => $name, 'type' => 'city', 'country_code' => 'FR', 'is_active' => true, 'default_language_id' => $french->id]
            );
        }

        Location::query()->updateOrCreate(
            ['slug' => 'united-states'],
            ['parent_id' => $global->id, 'name' => 'United States', 'type' => 'country', 'country_code' => 'US', 'is_active' => true, 'sort_order' => 3, 'default_language_id' => $english?->id, 'latitude' => 37.0902400, 'longitude' => -95.7128910, 'map_zoom' => 4, 'show_on_map' => true]
        );
    }
}
