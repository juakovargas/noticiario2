<?php

namespace App\Services\Scheduling;

use App\Models\EditorialSchedule;
use App\Models\EditorialScheduleRun;
use App\Services\PromptGeneration\BulletinPromptRunService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EditorialScheduleRunner
{
    public function __construct(private readonly BulletinPromptRunService $promptRunService)
    {
    }

    public function createDueRuns(Carbon $now = null, array $options = []): array
    {
        $now ??= now();
        $limit = (int) ($options['limit'] ?? 100);

        $dueSchedules = EditorialSchedule::query()
            ->with('bulletinType')
            ->where('is_active', true)
            ->when(isset($options['schedule_id']), fn ($q) => $q->whereKey($options['schedule_id']))
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', $now)
            ->orderBy('next_run_at')
            ->limit($limit)
            ->get();

        $summary = [
            'due_schedules' => $dueSchedules->count(),
            'runs_created' => 0,
            'prompt_runs_created' => 0,
            'prompts_generated' => 0,
            'skipped_duplicates' => 0,
            'skipped' => 0,
            'failed' => 0,
            'items' => [],
        ];

        foreach ($dueSchedules as $schedule) {
            $scheduledFor = Carbon::parse($schedule->next_run_at)->utc()->startOfMinute();

            try {
                $result = $this->createRunForSchedule($schedule, $scheduledFor, $options);
                $summary['runs_created'] += (int) $result['run_created'];
                $summary['prompt_runs_created'] += (int) $result['prompt_run_created'];
                $summary['prompts_generated'] += (int) $result['prompt_generated'];
                $summary['skipped_duplicates'] += (int) $result['duplicate'];
                $summary['skipped'] += (int) $result['skipped'];
                $summary['items'][] = $result;
            } catch (\Throwable $e) {
                $summary['failed']++;
                $summary['items'][] = ['schedule_id' => $schedule->id, 'status' => 'failed', 'message' => $e->getMessage()];
            }
        }

        return $summary;
    }

    public function createRunForSchedule(EditorialSchedule $schedule, Carbon $scheduledFor, array $options = []): array
    {
        if (! $schedule->is_active) {
            return ['schedule_id' => $schedule->id, 'status' => 'skipped', 'skipped' => 1, 'run_created' => 0, 'prompt_run_created' => 0, 'prompt_generated' => 0, 'duplicate' => 0];
        }

        $existing = EditorialScheduleRun::query()
            ->where('editorial_schedule_id', $schedule->id)
            ->where('scheduled_for', $scheduledFor)
            ->first();

        if ($existing) {
            return ['schedule_id' => $schedule->id, 'status' => 'duplicate', 'run_id' => $existing->id, 'skipped' => 0, 'run_created' => 0, 'prompt_run_created' => 0, 'prompt_generated' => 0, 'duplicate' => 1];
        }

        if (($options['dry_run'] ?? false) === true) {
            return ['schedule_id' => $schedule->id, 'status' => 'dry_run', 'scheduled_for' => $scheduledFor->toDateTimeString(), 'skipped' => 0, 'run_created' => 0, 'prompt_run_created' => 0, 'prompt_generated' => 0, 'duplicate' => 0];
        }

        $run = EditorialScheduleRun::query()->create([
            'editorial_schedule_id' => $schedule->id,
            'scheduled_for' => $scheduledFor,
            'status' => 'created',
        ]);

        $promptRun = null;
        $promptGenerated = false;
        $promptRunCreated = false;

        if (($schedule->auto_create_prompt_run ?? true) && $schedule->bulletinType) {
            $promptRun = $this->promptRunService->createFromBulletinType($schedule->bulletinType, null, $scheduledFor->toIso8601String());
            $promptRunCreated = true;

            $run->update([
                'bulletin_prompt_run_id' => $promptRun->id,
                'status' => 'prompt_run_created',
                'metadata' => array_merge((array) $run->metadata, ['created_from_schedule_runner' => true]),
            ]);

            if (($options['generate_prompts'] ?? false) || ($schedule->auto_generate_prompt ?? true)) {
                $this->promptRunService->generatePrompt($promptRun);
                $promptGenerated = true;
                $run->update(['status' => 'prompt_generated']);
            }
        }

        $schedule->forceFill([
            'last_run_at' => $scheduledFor,
            'next_run_at' => $this->calculateNextRunAt($schedule, $scheduledFor->copy()->addMinute()),
        ])->save();

        return [
            'schedule_id' => $schedule->id,
            'status' => 'created',
            'run_id' => $run->id,
            'run_created' => 1,
            'prompt_run_created' => $promptRunCreated ? 1 : 0,
            'prompt_generated' => $promptGenerated ? 1 : 0,
            'duplicate' => 0,
            'skipped' => 0,
        ];
    }

    public function calculateNextRunAt(EditorialSchedule $schedule, Carbon $from = null): ?Carbon
    {
        $timezone = $schedule->timezone ?: config('app.timezone');
        $fromLocal = ($from ?? now())->copy()->timezone($timezone);

        $frequency = $schedule->run_frequency ?: $schedule->frequency_type ?: 'daily';
        $time = $schedule->run_time ?: $schedule->scheduled_time ?: '08:00:00';

        $base = $fromLocal->copy();
        $base->setTimeFromTimeString(strlen($time) === 5 ? $time.':00' : $time);

        return match ($frequency) {
            'once' => $schedule->scheduled_date
                ? Carbon::parse($schedule->scheduled_date->toDateString().' '.$base->format('H:i:s'), $timezone)->utc()
                : $schedule->next_run_at,
            'weekly' => $this->nextWeekly($schedule, $base, $timezone),
            'monthly' => $this->nextMonthly($base),
            default => $this->nextDaily($base),
        };
    }

    private function nextDaily(Carbon $base): Carbon
    {
        return $base->isFuture() ? $base->utc() : $base->addDay()->utc();
    }

    private function nextMonthly(Carbon $base): Carbon
    {
        return $base->isFuture() ? $base->utc() : $base->addMonthNoOverflow()->utc();
    }

    private function nextWeekly(EditorialSchedule $schedule, Carbon $base, string $timezone): Carbon
    {
        $days = collect($schedule->run_days ?: $schedule->weekdays ?: ['mon'])
            ->map(fn ($d) => strtolower((string) $d))
            ->map(fn ($d) => ['sun' => 0, 'mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6][$d] ?? null)
            ->filter(fn ($d) => $d !== null)
            ->values();

        if ($days->isEmpty()) {
            return $this->nextDaily($base);
        }

        for ($i = 0; $i < 8; $i++) {
            $candidate = $base->copy()->addDays($i);
            if ($days->contains($candidate->dayOfWeek) && $candidate->isFuture()) {
                return $candidate->utc();
            }
        }

        return $base->copy()->addWeek()->utc();
    }
}
