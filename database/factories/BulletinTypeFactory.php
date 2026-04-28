<?php

namespace Database\Factories;

use App\Models\BulletinType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BulletinType>
 */
class BulletinTypeFactory extends Factory
{
    protected $model = BulletinType::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => $name,
            'slug' => str($name)->slug()->value(),
            'edition_type' => 'special',
            'is_active' => true,
            'default_timezone' => 'UTC',
        ];
    }
}
