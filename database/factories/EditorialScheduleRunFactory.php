<?php

namespace Database\Factories;

use App\Models\EditorialSchedule;
use App\Models\EditorialScheduleRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EditorialScheduleRun>
 */
class EditorialScheduleRunFactory extends Factory
{
    protected $model = EditorialScheduleRun::class;

    public function definition(): array
    {
        return [
            'editorial_schedule_id' => EditorialSchedule::factory(),
            'scheduled_for' => now(),
            'status' => 'created',
            'started_at' => now(),
        ];
    }
}
