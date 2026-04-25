<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\NewsCategory;
use App\Models\NewsItem;
use App\Models\NewsSource;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<NewsItem>
 */
class NewsItemFactory extends Factory
{
    protected $model = NewsItem::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'news_source_id' => NewsSource::factory(),
            'news_category_id' => NewsCategory::factory(),
            'location_id' => Location::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'summary' => fake()->sentence(),
            'status' => 'draft',
            'editorial_priority' => 3,
            'is_evergreen' => false,
        ];
    }
}
