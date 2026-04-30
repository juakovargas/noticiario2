<?php

namespace App\Console\Commands;

use App\Models\EditorialSchedule;
use App\Services\Pipelines\BulletinPromptRunPipeline;
use App\Services\Scheduling\EditorialScheduleRunner;
use Illuminate\Console\Command;

class RunScheduleNowCommand extends Command
{
    protected $signature = 'noticiario:run-schedule-now {editorialScheduleId} {--allow-ai} {--full-pipeline} {--dry-run}';
    protected $description = 'Create schedule run now and optionally run full pipeline.';

    public function handle(EditorialScheduleRunner $runner, BulletinPromptRunPipeline $pipeline): int
    {
        $schedule = EditorialSchedule::query()->with('bulletinType')->findOrFail((int) $this->argument('editorialScheduleId'));
        $run = $runner->createRunForSchedule($schedule, now()->utc()->startOfMinute(), ['generate_prompts' => true, 'dry_run' => (bool) $this->option('dry-run')]);

        if ($this->option('full-pipeline') && $run->bulletinPromptRun && ! $this->option('dry-run')) {
            $summary = $pipeline->run($run->bulletinPromptRun->refresh(), null, [
                'allow_ai_call' => (bool) $this->option('allow-ai'),
                'generate_metadata' => true,
                'extract_sources' => true,
            ]);
            $this->line(json_encode($summary, JSON_PRETTY_PRINT));
            return $summary['success'] ? self::SUCCESS : self::FAILURE;
        }

        $this->info('Schedule run created.');
        return self::SUCCESS;
    }
}
