<?php

namespace Database\Factories;

use App\Models\EditorialSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EditorialSchedule>
 */
class EditorialScheduleFactory extends Factory
{
    protected $model = EditorialSchedule::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => $name,
            'slug' => str($name)->slug()->value(),
            'edition_type' => 'morning',
            'frequency_type' => 'daily',
            'run_frequency' => 'daily',
            'scheduled_time' => '08:00:00',
            'run_time' => '08:00:00',
            'timezone' => 'UTC',
            'is_active' => true,
            'auto_create_prompt_run' => true,
            'auto_generate_prompt' => true,
            'auto_run_pipeline' => false,
            'auto_generate_ai_response' => false,
            'auto_create_script' => true,
            'auto_generate_metadata' => true,
            'auto_extract_sources' => true,
            'next_run_at' => now()->addHour(),
        ];
    }
}
