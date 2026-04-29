<?php

namespace App\Services\Scheduling;

use App\Models\EditorialSchedule;
use App\Models\EditorialScheduleRun;
use App\Services\PromptGeneration\BulletinPromptRunService;
use Carbon\Carbon;

class EditorialScheduleRunner
{
    public function __construct(private readonly BulletinPromptRunService $promptRunService)
    {
    }

    public function createDueRuns(?Carbon $now = null, array $options = []): array
    {
        $now ??= now();
        $dryRun = (bool) ($options['dry_run'] ?? false);

        $dueSchedules = EditorialSchedule::query()
            ->with('bulletinType')
            ->where('is_active', true)
            ->when(isset($options['schedule_id']), fn ($q) => $q->whereKey((int) $options['schedule_id']))
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', $now)
            ->orderBy('next_run_at')
            ->limit((int) ($options['limit'] ?? 50))
            ->get();

        $summary = [
            'due_schedules' => $dueSchedules->count(),
            'runs_created' => 0,
            'prompt_runs_created' => 0,
            'prompts_generated' => 0,
            'duplicates_skipped' => 0,
            'failed' => 0,
            'dry_run' => $dryRun,
            'messages' => [],
        ];

        foreach ($dueSchedules as $schedule) {
            $scheduledFor = Carbon::parse($schedule->next_run_at)->utc()->startOfMinute();

            try {
                $run = $this->createRunForSchedule($schedule, $scheduledFor, $options);
                $isDuplicate = $run->wasRecentlyCreated === false && $run->scheduled_for?->equalTo($scheduledFor);

                $summary['runs_created'] += $run->wasRecentlyCreated ? 1 : 0;
                $summary['duplicates_skipped'] += $isDuplicate ? 1 : 0;
                $summary['prompt_runs_created'] += $run->bulletin_prompt_run_id ? 1 : 0;
                $summary['prompts_generated'] += $run->status === 'prompt_generated' ? 1 : 0;
            } catch (\Throwable $e) {
                $summary['failed']++;
                $summary['messages'][] = sprintf('schedule %d failed: %s', $schedule->id, $e->getMessage());
            }
        }

        return $summary;
    }

    public function createRunForSchedule(EditorialSchedule $schedule, Carbon $scheduledFor, array $options = []): EditorialScheduleRun
    {
        $existing = EditorialScheduleRun::query()
            ->where('editorial_schedule_id', $schedule->id)
            ->where('scheduled_for', $scheduledFor)
            ->first();

        if ($existing) {
            return $existing;
        }

        if (($options['dry_run'] ?? false) === true) {
            return new EditorialScheduleRun([
                'editorial_schedule_id' => $schedule->id,
                'scheduled_for' => $scheduledFor,
                'status' => 'created',
            ]);
        }

        $run = EditorialScheduleRun::query()->create([
            'editorial_schedule_id' => $schedule->id,
            'scheduled_for' => $scheduledFor,
            'status' => 'created',
            'started_at' => now(),
        ]);

        if (($schedule->auto_create_prompt_run ?? true) && $schedule->bulletinType) {
            $promptRun = $this->promptRunService->createFromBulletinType($schedule->bulletinType, null, $scheduledFor->toIso8601String());

            $promptRun->forceFill([
                'editorial_schedule_id' => $schedule->id,
                'editorial_schedule_run_id' => $run->id,
            ])->save();

            $run->forceFill([
                'bulletin_prompt_run_id' => $promptRun->id,
                'status' => 'prompt_run_created',
            ])->save();

            if (($options['generate_prompts'] ?? false) || ($schedule->auto_generate_prompt ?? true)) {
                $this->promptRunService->generatePrompt($promptRun);
                $run->forceFill(['status' => 'prompt_generated'])->save();
            }
        }

        $schedule->forceFill([
            'last_run_at' => $scheduledFor,
            'next_run_at' => $schedule->run_frequency === 'once'
                ? null
                : $this->calculateNextRunAt($schedule, $scheduledFor->copy()->addMinute()),
        ])->save();

        return $run->refresh();
    }

    public function calculateNextRunAt(EditorialSchedule $schedule, ?Carbon $from = null): ?Carbon
    {
        $timezone = $schedule->timezone ?: config('app.timezone');
        $fromLocal = ($from ?? now())->copy()->timezone($timezone);
        $runTime = strlen((string) ($schedule->run_time ?: '08:00')) === 5 ? ($schedule->run_time.':00') : ($schedule->run_time ?: '08:00:00');

        return match ($schedule->run_frequency ?: 'daily') {
            'once' => null,
            'weekly' => $this->nextWeekly($schedule, $fromLocal, $runTime)->utc(),
            'monthly' => $this->nextMonthly($schedule, $fromLocal, $runTime)->utc(),
            'custom' => $schedule->next_run_at,
            default => $this->nextDaily($fromLocal, $runTime)->utc(),
        };
    }

    public function isDue(EditorialSchedule $schedule, ?Carbon $now = null): bool
    {
        $now ??= now();

        return (bool) $schedule->is_active && $schedule->next_run_at !== null && Carbon::parse($schedule->next_run_at)->lessThanOrEqualTo($now);
    }

    private function nextDaily(Carbon $fromLocal, string $runTime): Carbon
    {
        $candidate = $fromLocal->copy()->setTimeFromTimeString($runTime);

        return $candidate->greaterThan($fromLocal) ? $candidate : $candidate->addDay();
    }

    private function nextWeekly(EditorialSchedule $schedule, Carbon $fromLocal, string $runTime): Carbon
    {
        $dayMap = ['sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6];
        $days = collect($schedule->run_days ?? [])->map(function ($value) use ($dayMap) {
            if (is_numeric($value)) {
                return ((int) $value) % 7;
            }
            return $dayMap[strtolower((string) $value)] ?? null;
        })->filter(fn ($day) => $day !== null)->values();

        if ($days->isEmpty()) {
            return $this->nextDaily($fromLocal, $runTime);
        }

        for ($i = 0; $i < 14; $i++) {
            $candidate = $fromLocal->copy()->addDays($i)->setTimeFromTimeString($runTime);
            if ($days->contains($candidate->dayOfWeek) && $candidate->greaterThan($fromLocal)) {
                return $candidate;
            }
        }

        return $fromLocal->copy()->addWeek();
    }

    private function nextMonthly(EditorialSchedule $schedule, Carbon $fromLocal, string $runTime): Carbon
    {
        $monthDay = (int) data_get($schedule->metadata, 'month_day', $schedule->next_run_at?->timezone($fromLocal->timezone)->day ?? 1);
        $monthDay = max(1, min(28, $monthDay));

        $candidate = $fromLocal->copy()->day($monthDay)->setTimeFromTimeString($runTime);

        return $candidate->greaterThan($fromLocal) ? $candidate : $candidate->addMonthNoOverflow();
    }
}
