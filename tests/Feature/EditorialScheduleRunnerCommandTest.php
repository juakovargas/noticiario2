<?php

namespace Tests\Feature;

use App\Models\BulletinType;
use App\Models\EditorialSchedule;
use App\Models\EditorialScheduleRun;
use App\Services\Scheduling\EditorialScheduleRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditorialScheduleRunnerCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_due_run_for_active_schedule(): void
    {
        $schedule = $this->makeSchedule(['is_active' => true, 'next_run_at' => now()->subMinute()]);

        $result = app(EditorialScheduleRunner::class)->createDueRuns(now(), []);

        $this->assertSame(1, $result['runs_created']);
        $this->assertDatabaseHas('editorial_schedule_runs', ['editorial_schedule_id' => $schedule->id]);
    }

    public function test_it_does_not_create_for_inactive_schedule(): void
    {
        $this->makeSchedule(['is_active' => false, 'next_run_at' => now()->subMinute()]);

        $result = app(EditorialScheduleRunner::class)->createDueRuns(now(), []);

        $this->assertSame(0, $result['runs_created']);
    }

    public function test_it_skips_duplicates(): void
    {
        $schedule = $this->makeSchedule(['next_run_at' => now()->subMinute()->startOfMinute()]);
        EditorialScheduleRun::query()->create([
            'editorial_schedule_id' => $schedule->id,
            'scheduled_for' => $schedule->next_run_at,
            'status' => 'created',
        ]);

        $result = app(EditorialScheduleRunner::class)->createDueRuns(now(), []);

        $this->assertSame(1, $result['skipped_duplicates']);
    }

    public function test_command_dry_run_creates_nothing(): void
    {
        $this->makeSchedule(['next_run_at' => now()->subMinute()]);

        $this->artisan('noticiario:create-due-runs --dry-run')->assertExitCode(0);

        $this->assertDatabaseCount('editorial_schedule_runs', 0);
    }

    private function makeSchedule(array $overrides = []): EditorialSchedule
    {
        $bulletinType = BulletinType::query()->create([
            'name' => 'Daily Test',
            'slug' => 'daily-test-'.str()->random(6),
            'edition_type' => 'morning',
            'coverage_mode' => 'today_so_far',
            'output_mode' => 'structured',
            'is_active' => true,
        ]);

        return EditorialSchedule::query()->create(array_merge([
            'name' => 'Schedule '.str()->random(4),
            'slug' => 'schedule-'.str()->random(6),
            'frequency_type' => 'daily',
            'run_frequency' => 'daily',
            'scheduled_time' => '08:00',
            'run_time' => '08:00',
            'timezone' => 'UTC',
            'is_active' => true,
            'auto_create_prompt_run' => true,
            'auto_generate_prompt' => false,
            'auto_generate_ai_response' => false,
            'next_run_at' => now()->subMinute()->startOfMinute(),
            'bulletin_type_id' => $bulletinType->id,
            'edition_type' => 'morning',
        ], $overrides));
    }
}
