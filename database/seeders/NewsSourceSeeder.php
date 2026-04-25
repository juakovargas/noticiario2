<?php

namespace Database\Seeders;

use App\Models\NewsSource;
use Illuminate\Database\Seeder;

class NewsSourceSeeder extends Seeder
{
    public function run(): void
    {
        NewsSource::query()->updateOrCreate(
            ['slug' => 'manual-source'],
            [
                'name' => 'Manual Source',
                'type' => 'manual',
                'is_active' => true,
                'trust_level' => 3,
            ]
        );
    }
}
