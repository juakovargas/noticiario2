<?php

namespace Database\Factories;

use App\Models\BulletinPromptRun;
use App\Models\BulletinType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BulletinPromptRun>
 */
class BulletinPromptRunFactory extends Factory
{
    protected $model = BulletinPromptRun::class;

    public function definition(): array
    {
        return [
            'bulletin_type_id' => BulletinType::factory(),
            'title' => fake()->sentence(4),
            'scheduled_for' => now()->addHour(),
            'status' => 'draft',
        ];
    }
}
