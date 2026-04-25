<?php

namespace Database\Seeders;

use App\Models\NewsCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NewsCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = ['Politics', 'Sports', 'Culture', 'Science', 'Technology', 'Economy', 'Esports', 'General'];

        foreach ($categories as $index => $name) {
            NewsCategory::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );
        }
    }
}
