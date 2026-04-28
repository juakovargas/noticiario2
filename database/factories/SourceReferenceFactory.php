<?php

namespace Database\Factories;

use App\Models\NewsItem;
use App\Models\Script;
use App\Models\SourceReference;
use Illuminate\Database\Eloquent\Factories\Factory;

class SourceReferenceFactory extends Factory
{
    protected $model = SourceReference::class;

    public function definition(): array
    {
        return [
            'news_item_id' => NewsItem::factory(),
            'script_id' => Script::factory(),
            'title' => fake()->sentence(4),
            'source_name' => fake()->company(),
            'source_url' => fake()->url(),
            'source_type' => 'web',
            'verification_status' => 'pending',
            'trust_level' => fake()->numberBetween(0, 10),
        ];
    }
}
