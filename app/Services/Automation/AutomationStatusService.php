<?php

namespace App\Services\Automation;

use App\Models\AiProvider;
use App\Models\AiRequestLog;
use App\Models\BulletinPromptRun;
use App\Models\BulletinType;
use App\Models\EditorialSchedule;
use App\Models\EditorialScheduleRun;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AutomationStatusService
{
    public function getBulletinAutomationOverview(): Collection
    {
        $hasActiveProvider = AiProvider::query()->where('is_active', true)->exists();

        return BulletinType::query()
            ->with([
                'language:id,name,code',
                'location:id,name',
                'newsCategory:id,name',
                'schedules' => fn ($q) => $q->orderBy('run_time')->orderBy('scheduled_time'),
            ])
            ->get()
            ->map(function (BulletinType $bulletinType) use ($hasActiveProvider): array {
                $schedules = $bulletinType->schedules ?? collect();
                $latestExecution = $this->getLatestExecutionSummary($bulletinType);
                $attentionReasons = $this->getAttentionReasons($bulletinType, $schedules, $latestExecution, $hasActiveProvider);

                return [
                    'id' => $bulletinType->id,
                    'name' => $bulletinType->name,
                    'language' => $bulletinType->language?->name,
                    'location' => $bulletinType->location?->name,
                    'category' => $bulletinType->newsCategory?->name,
                    'edition_type' => $bulletinType->edition_type,
                    'target_duration_seconds' => $bulletinType->target_duration_seconds,
                    'active_schedules_count' => $schedules->where('is_active', true)->count(),
                    'total_schedules_count' => $schedules->count(),
                    'schedules' => $schedules->map(fn (EditorialSchedule $schedule) => $this->getScheduleStatus($schedule))->values(),
                    'latest_execution' => $latestExecution,
                    'needs_manual_attention' => count($attentionReasons) > 0,
                    'attention_reasons' => array_values(array_unique($attentionReasons)),
                ];
            });
    }

    public function getScheduleStatus(EditorialSchedule $schedule): array
    {
        $nextRunAt = $schedule->next_run_at instanceof Carbon ? $schedule->next_run_at : optional($schedule->next_run_at ? Carbon::parse($schedule->next_run_at) : null);
        $isOverdue = (bool) $schedule->is_active && $nextRunAt && $nextRunAt->isPast();

        return [
            'id' => $schedule->id,
            'is_active' => (bool) $schedule->is_active,
            'frequency' => $schedule->run_frequency ?: $schedule->frequency_type,
            'run_time' => $schedule->run_time ?: $schedule->scheduled_time,
            'run_days' => $schedule->run_days ?: $schedule->weekdays,
            'timezone' => $schedule->timezone,
            'next_run_at' => $schedule->next_run_at?->toIso8601String(),
            'last_run_at' => $schedule->last_run_at?->toIso8601String(),
            'last_success_at' => $schedule->last_success_at?->toIso8601String(),
            'last_failure_at' => $schedule->last_failure_at?->toIso8601String(),
            'last_error_message' => $schedule->last_error_message,
            'auto_create_prompt_run' => (bool) $schedule->auto_create_prompt_run,
            'auto_generate_prompt' => (bool) $schedule->auto_generate_prompt,
            'auto_run_pipeline' => (bool) $schedule->auto_run_pipeline,
            'auto_generate_ai_response' => (bool) $schedule->auto_generate_ai_response,
            'auto_create_script' => (bool) $schedule->auto_create_script,
            'auto_generate_metadata' => (bool) $schedule->auto_generate_metadata,
            'auto_extract_sources' => (bool) $schedule->auto_extract_sources,
            'is_overdue' => $isOverdue,
            'missing_next_run_at' => (bool) $schedule->is_active && ! $schedule->next_run_at,
            'status_label' => $isOverdue ? 'overdue' : ((bool) $schedule->is_active ? 'active' : 'inactive'),
        ];
    }

    public function getLatestExecutionSummary(BulletinType $bulletinType): array
    {
        $latestScheduleRun = EditorialScheduleRun::query()
            ->whereHas('editorialSchedule', fn ($q) => $q->where('bulletin_type_id', $bulletinType->id))
            ->latest('created_at')
            ->first();

        $latestPromptRun = BulletinPromptRun::query()
            ->where('bulletin_type_id', $bulletinType->id)
            ->latest('created_at')
            ->first();

        $latestScript = $latestPromptRun?->script;

        $latestAiRequest = $latestPromptRun
            ? AiRequestLog::query()->where('bulletin_prompt_run_id', $latestPromptRun->id)->latest('created_at')->first()
            : null;

        return [
            'schedule_run' => $latestScheduleRun ? [
                'id' => $latestScheduleRun->id,
                'status' => $latestScheduleRun->status,
                'error_message' => $latestScheduleRun->error_message,
                'created_at' => $latestScheduleRun->created_at?->toIso8601String(),
                'updated_at' => $latestScheduleRun->updated_at?->toIso8601String(),
            ] : null,
            'prompt_run' => $latestPromptRun ? [
                'id' => $latestPromptRun->id,
                'status' => $latestPromptRun->status,
                'error_message' => $latestPromptRun->error_message,
                'created_at' => $latestPromptRun->created_at?->toIso8601String(),
                'updated_at' => $latestPromptRun->updated_at?->toIso8601String(),
            ] : null,
            'script' => $latestScript ? [
                'id' => $latestScript->id,
                'title' => $latestScript->title,
                'created_at' => $latestScript->created_at?->toIso8601String(),
                'updated_at' => $latestScript->updated_at?->toIso8601String(),
            ] : null,
            'ai_request' => $latestAiRequest ? [
                'id' => $latestAiRequest->id,
                'status' => $latestAiRequest->status,
                'error_message' => $latestAiRequest->error_message,
                'created_at' => $latestAiRequest->created_at?->toIso8601String(),
                'updated_at' => $latestAiRequest->updated_at?->toIso8601String(),
            ] : null,
        ];
    }

    private function getAttentionReasons(BulletinType $bulletinType, Collection $schedules, array $latestExecution, bool $hasActiveProvider): array
    {
        $reasons = [];

        if ($schedules->isEmpty()) {
            $reasons[] = 'no_schedules_configured';
        }

        foreach ($schedules as $schedule) {
            if (! $schedule->is_active) {
                continue;
            }

            if (! $schedule->next_run_at) {
                $reasons[] = 'active_schedule_missing_next_run';
            }

            if ($schedule->next_run_at && Carbon::parse($schedule->next_run_at)->isPast()) {
                $reasons[] = 'active_schedule_overdue';
            }

            if ($schedule->auto_generate_ai_response && ! $hasActiveProvider) {
                $reasons[] = 'missing_ai_provider';
            }

            if ($schedule->auto_create_prompt_run && empty($latestExecution['prompt_run'])) {
                $reasons[] = 'missing_prompt_run';
            }
        }

        if (($latestExecution['ai_request']['status'] ?? null) === 'failed') {
            $reasons[] = 'latest_ai_request_failed';
        }

        if (($latestExecution['prompt_run']['status'] ?? null) === 'pipeline_failed') {
            $reasons[] = 'latest_pipeline_failed';
        }

        if (($latestExecution['schedule_run']['status'] ?? null) === 'failed') {
            $reasons[] = 'latest_schedule_run_failed';
        }

        return $reasons;
    }
}
