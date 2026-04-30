<?php

namespace App\Services\Automation;

use App\Models\AiProvider;
use App\Models\AiRequestLog;
use App\Models\BulletinPromptRun;
use App\Models\BulletinType;
use App\Models\EditorialSchedule;
use App\Models\EditorialScheduleRun;
use App\Models\Script;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AutomationStatusService
{
    public function getBulletinAutomationOverview(): Collection
    {
        $hasActiveProvider = AiProvider::query()->where('is_active', true)->exists();

        return BulletinType::query()->with(['language:id,name,code','location:id,name','newsCategory:id,name','schedules' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('run_time')->orderBy('scheduled_time'),'primarySchedule'])->get()
            ->map(fn (BulletinType $bulletinType) => $this->mapBulletinStatus($bulletinType, $hasActiveProvider));
    }

    private function mapBulletinStatus(BulletinType $bulletinType, bool $hasActiveProvider): array
    {
        $schedule = $bulletinType->primarySchedule ?? $bulletinType->schedules->first();
        $latestExecution = $this->getLatestExecutionSummary($bulletinType);
        $latestPromptRun = $latestExecution['prompt_run'];
        $latestScript = $latestExecution['script'];

        $nextRunStatus = 'missing';
        $nextRunStatusLabelKey = 'automation.next_run.missing_schedule_configuration';
        $overdueMinutes = null;

        if ($schedule) {
            if (! $schedule->is_active) {
                $nextRunStatus = 'inactive';
                $nextRunStatusLabelKey = 'automation.next_run.inactive';
            } elseif (! $schedule->next_run_at) {
                $nextRunStatus = 'missing';
                $nextRunStatusLabelKey = 'automation.next_run.missing';
            } else {
                $nextRun = Carbon::parse($schedule->next_run_at);
                $minutes = now()->diffInMinutes($nextRun, false) * -1;
                if ($minutes > 0) {
                    $nextRunStatus = 'overdue';
                    $nextRunStatusLabelKey = 'automation.next_run.overdue_since';
                    $overdueMinutes = $minutes;
                } else {
                    $nextRunStatus = 'scheduled';
                    $nextRunStatusLabelKey = 'automation.next_run.scheduled';
                }
            }
        }

        $attentionReasonKeys = $this->getAttentionReasonKeys($bulletinType, $schedule, $latestExecution, $hasActiveProvider, $nextRunStatus);

        return [
            'id' => $bulletinType->id,
            'name' => $bulletinType->name,
            'location' => $bulletinType->location?->name,
            'category' => $bulletinType->newsCategory?->name,
            'language' => $bulletinType->language?->name,
            'edition_type' => $bulletinType->edition_type,
            'target_duration_seconds' => $bulletinType->target_duration_seconds,
            'scope_label' => collect([$bulletinType->location?->name, $bulletinType->newsCategory?->name, $bulletinType->language?->name, $bulletinType->edition_type])->filter()->join(' · '),
            'automation_enabled' => (bool) ($schedule?->is_active),
            'schedule_status_label_key' => $schedule ? ($schedule->is_active ? 'Automation on' : 'Automation off') : 'Missing schedule',
            'has_schedule' => (bool) $schedule,
            'schedule_id' => $schedule?->id,
            'frequency_label' => $schedule?->run_frequency ?: $schedule?->frequency_type,
            'run_time_label' => $this->formatTime($schedule?->run_time ?: $schedule?->scheduled_time),
            'days_label' => is_array($schedule?->run_days) ? implode(', ', $schedule->run_days) : (is_array($schedule?->weekdays) ? implode(', ', $schedule->weekdays) : null),
            'timezone' => $schedule?->timezone,
            'next_run_at' => $schedule?->next_run_at?->toIso8601String(),
            'next_run_status' => $nextRunStatus,
            'next_run_status_label_key' => $nextRunStatusLabelKey,
            'overdue_minutes' => $overdueMinutes,
            'last_run_at' => $schedule?->last_run_at?->toIso8601String(),
            'last_run_status' => $latestExecution['schedule_run']['status'] ?? null,
            'last_run_status_label_key' => $this->statusKeyToTranslation($latestExecution['schedule_run']['status'] ?? null),
            'last_success_at' => $schedule?->last_success_at?->toIso8601String(),
            'last_failure_at' => $schedule?->last_failure_at?->toIso8601String(),
            'latest_prompt_run' => $latestPromptRun,
            'latest_script' => $latestScript,
            'scripts_count' => Script::query()->whereHas('bulletinPromptRun', fn ($q) => $q->where('bulletin_type_id', $bulletinType->id))->count(),
            'prompt_runs_count' => BulletinPromptRun::query()->where('bulletin_type_id', $bulletinType->id)->count(),
            'attention_level' => $nextRunStatus === 'overdue' ? (($overdueMinutes ?? 0) >= 180 ? 'critical' : 'warning') : (count($attentionReasonKeys) ? 'info' : 'none'),
            'attention_reason_keys' => $attentionReasonKeys,
            'available_actions' => ['toggle' => (bool) $schedule, 'run_now' => (bool) $schedule, 'run_overdue_now' => (bool) ($schedule && $nextRunStatus === 'overdue'),'recalculate_next_run' => (bool) $schedule,'sync_schedule' => ! $schedule,'view_executions' => true,'view_scripts' => true,'edit_bulletin' => true,'edit_schedule' => (bool) $schedule],
        ];
    }

    private function formatTime(?string $time): ?string { if (! $time) return null; return substr($time,0,5); }
    private function statusKeyToTranslation(?string $status): string { return match($status){'prompt_generated'=>'Prompt generated','script_created'=>'Script created','failed'=>'Failed','completed'=>'Completed','pipeline_failed'=>'Pipeline failed', default=>'No executions yet'}; }

    public function getLatestExecutionSummary(BulletinType $bulletinType): array { /* unchanged simplified */
        $latestScheduleRun = EditorialScheduleRun::query()->whereHas('editorialSchedule', fn ($q) => $q->where('bulletin_type_id', $bulletinType->id))->latest('created_at')->first();
        $latestPromptRun = BulletinPromptRun::query()->where('bulletin_type_id', $bulletinType->id)->latest('created_at')->first();
        $latestScript = $latestPromptRun?->script;
        $latestAiRequest = $latestPromptRun ? AiRequestLog::query()->where('bulletin_prompt_run_id', $latestPromptRun->id)->latest('created_at')->first() : null;
        return ['schedule_run'=>$latestScheduleRun?['id'=>$latestScheduleRun->id,'status'=>$latestScheduleRun->status,'created_at'=>$latestScheduleRun->created_at?->toIso8601String()]:null,'prompt_run'=>$latestPromptRun?['id'=>$latestPromptRun->id,'status'=>$latestPromptRun->status,'created_at'=>$latestPromptRun->created_at?->toIso8601String()]:null,'script'=>$latestScript?['id'=>$latestScript->id,'title'=>$latestScript->title,'created_at'=>$latestScript->created_at?->toIso8601String()]:null,'ai_request'=>$latestAiRequest?['id'=>$latestAiRequest->id,'status'=>$latestAiRequest->status,'created_at'=>$latestAiRequest->created_at?->toIso8601String()]:null];
    }

    private function getAttentionReasonKeys(BulletinType $bulletinType, ?EditorialSchedule $schedule, array $latestExecution, bool $hasActiveProvider, string $nextRunStatus): array
    {
        $reasons = [];
        if (! $schedule) { $reasons[] = $bulletinType->default_schedule_time || $bulletinType->default_run_time ? 'automation.attention.schedule_not_synchronized' : 'automation.attention.missing_schedule'; }
        if ($nextRunStatus === 'overdue') $reasons[] = 'automation.attention.schedule_overdue';
        if (($latestExecution['ai_request']['status'] ?? null) === 'failed') $reasons[] = 'automation.attention.ai_request_failed';
        if (($latestExecution['prompt_run']['status'] ?? null) === 'pipeline_failed') $reasons[] = 'automation.attention.pipeline_failed';
        if (($schedule?->auto_generate_ai_response ?? false) && ! $hasActiveProvider) $reasons[] = 'automation.attention.missing_ai_provider';
        return array_values(array_unique($reasons));
    }
}
