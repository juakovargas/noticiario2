<?php

namespace Database\Factories;

use App\Models\Edition;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Edition>
 */
class EditionFactory extends Factory
{
    protected $model = Edition::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'location_id' => Location::factory(),
            'title' => ucfirst($title),
            'slug' => Str::slug($title),
            'edition_type' => 'morning',
            'status' => 'draft',
            'language' => 'en',
        ];
    }
}
