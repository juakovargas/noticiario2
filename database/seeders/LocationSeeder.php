<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $global = Location::query()->updateOrCreate(
            ['slug' => 'global'],
            ['name' => 'Global', 'type' => 'global', 'is_active' => true, 'sort_order' => 0]
        );

        $spain = Location::query()->updateOrCreate(
            ['slug' => 'spain'],
            ['parent_id' => $global->id, 'name' => 'Spain', 'type' => 'country', 'country_code' => 'ES', 'is_active' => true, 'sort_order' => 1]
        );

        Location::query()->updateOrCreate(
            ['slug' => 'madrid'],
            ['parent_id' => $spain->id, 'name' => 'Madrid', 'type' => 'city', 'country_code' => 'ES', 'is_active' => true, 'sort_order' => 1]
        );

        Location::query()->updateOrCreate(
            ['slug' => 'france'],
            ['parent_id' => $global->id, 'name' => 'France', 'type' => 'country', 'country_code' => 'FR', 'is_active' => true, 'sort_order' => 2]
        );
    }
}
