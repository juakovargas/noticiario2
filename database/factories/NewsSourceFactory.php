<?php

namespace Database\Factories;

use App\Models\NewsSource;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<NewsSource>
 */
class NewsSourceFactory extends Factory
{
    protected $model = NewsSource::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'type' => 'manual',
            'is_active' => true,
            'is_demo' => false,
            'trust_level' => 3,
            'language' => 'en',
        ];
    }
}
