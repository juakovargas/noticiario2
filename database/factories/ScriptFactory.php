<?php

namespace Database\Factories;

use App\Models\Edition;
use App\Models\Script;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Script>
 */
class ScriptFactory extends Factory
{
    protected $model = Script::class;

    public function definition(): array
    {
        return [
            'edition_id' => Edition::factory(),
            'title' => fake()->sentence(3),
            'status' => 'draft',
            'review_status' => 'pending',
            'language' => 'en',
        ];
    }
}
