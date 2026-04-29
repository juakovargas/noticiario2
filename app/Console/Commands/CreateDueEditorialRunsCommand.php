<?php

namespace App\Console\Commands;

use App\Models\EditorialSchedule;
use App\Services\Scheduling\EditorialScheduleRunner;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CreateDueEditorialRunsCommand extends Command
{
    protected $signature = 'noticiario:create-due-runs
        {--now= : Override current datetime}
        {--dry-run : Preview due runs without writing records}
        {--schedule-id= : Process only one schedule id}
        {--generate-prompts : Generate prompts for created prompt runs}
        {--limit=100 : Maximum schedules to process}';

    protected $description = 'Create due editorial schedule runs without calling external AI providers.';

    public function handle(EditorialScheduleRunner $runner): int
    {
        $now = $this->option('now') ? Carbon::parse((string) $this->option('now')) : now();
        $scheduleId = $this->option('schedule-id');

        if ($scheduleId && ! EditorialSchedule::query()->whereKey($scheduleId)->exists()) {
            $this->error('Schedule not found.');

            return self::FAILURE;
        }

        $summary = $runner->createDueRuns($now, [
            'dry_run' => (bool) $this->option('dry-run'),
            'schedule_id' => $scheduleId ? (int) $scheduleId : null,
            'generate_prompts' => (bool) $this->option('generate-prompts'),
            'limit' => (int) $this->option('limit'),
        ]);

        $this->info('Schedule runner');
        $this->line('Due schedules found: '.$summary['due_schedules']);
        $this->line('Due runs created: '.$summary['runs_created']);
        $this->line('Prompt runs created: '.$summary['prompt_runs_created']);
        $this->line('Prompts generated: '.$summary['prompts_generated']);
        $this->line('Duplicate runs skipped: '.$summary['duplicates_skipped']);
        $this->line('Failed schedules: '.$summary['failed']);
        $this->line('This command does not call external AI providers.');

        if ($summary['due_schedules'] === 0) {
            $this->comment('No due schedules found.');
        }

        return self::SUCCESS;
    }
}
