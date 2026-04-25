<?php

namespace Database\Factories;

use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Language>
 */
class LanguageFactory extends Factory
{
    protected $model = Language::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->languageCode(),
            'native_name' => null,
            'code' => fake()->unique()->lexify('??'),
            'flag_emoji' => '🌐',
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 0,
        ];
    }
}
