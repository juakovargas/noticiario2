<?php

namespace App\Services\Editor;

use App\Models\AiProvider;
use App\Models\BulletinPromptRun;
use App\Models\BulletinType;
use App\Models\EditorialSchedule;
use App\Models\EditorialScheduleRun;
use App\Models\Script;
use App\Models\SourceReference;
use Illuminate\Support\Collection;

class EditorDashboardOverviewService
{
    public function build(): array
    {
        $today = now();

        $schedules = EditorialSchedule::query()
            ->with([
                'bulletinType:id,name,is_active,location_id,news_category_id,ai_provider_id',
                'bulletinType.aiProvider:id,name,is_active,default_model,supports_grounding,provider_category,rate_limited_until',
                'location:id,name',
                'newsCategory:id,name',
                'runs' => fn ($q) => $q->latest('scheduled_for')->limit(1),
            ])
            ->get();

        $bulletins = BulletinType::query()->with(['location:id,name', 'newsCategory:id,name', 'aiProvider:id,name,default_model'])->get();

        return [
            'summary' => $this->buildSummary($schedules, $today),
            'mapOverview' => $this->buildMapOverview($schedules),
            'aiProviderStatus' => $this->buildAiProviderStatus($today, $bulletins),
            'actionableQueue' => $this->buildActionableQueue($schedules, $today),
            'scheduledBulletins' => $this->buildScheduledBulletins($schedules),
            'coverageOverview' => $this->buildCoverageOverview($schedules),
            'latestExecutions' => $this->buildLatestExecutions(),
            'editorialAlerts' => $this->buildEditorialAlerts($schedules, $today),
        ];
    }

    private function buildSummary(Collection $schedules, \Carbon\Carbon $today): array
    {
        $queue = $this->buildActionableQueue($schedules, $today);
        return [
            'activeBulletins' => $schedules->filter(fn ($s) => $s->is_active && $s->bulletinType?->is_active)->count(),
            'pendingTasks' => $queue->count(),
            'overdueSchedules' => $schedules->filter(fn ($s) => $s->is_active && $s->next_run_at && $s->next_run_at->isPast())->count(),
            'failedRunsToday' => EditorialScheduleRun::query()->whereDate('scheduled_for', $today->toDateString())->where('status', 'failed')->count(),
            'scriptsPendingReview' => Script::query()->where('review_status', 'pending')->where('status', '!=', 'archived')->count(),
            'sourcesPendingVerification' => SourceReference::query()->withoutArchived()->where('verification_status', 'pending')->count(),
            'readyForProduction' => Script::query()->where('status', '!=', 'archived')->where('production_status', 'ready_for_production')->count(),
        ];
    }

    private function buildActionableQueue(Collection $schedules, \Carbon\Carbon $today): Collection
    {
        $scheduleItems = $schedules->filter(function ($s) {
            if (! $s->bulletinType) return false;
            if (! $s->is_active || ! $s->bulletinType->is_active) return $this->needsAttention($s);
            return ($s->next_run_at && $s->next_run_at->isPast()) || ($s->next_run_at && $s->next_run_at->isToday()) || $this->hasFailedRunToday((int) $s->bulletin_type_id);
        })->map(fn ($s) => [
            'type' => 'schedule',
            'id' => 'schedule-'.$s->id,
            'bulletin' => $s->bulletinType?->name,
            'bulletin_id' => $s->bulletinType?->id,
            'location' => $s->location?->name,
            'category' => $s->newsCategory?->name,
            'provider' => $s->bulletinType?->aiProvider?->name,
            'model' => $s->bulletinType?->aiProvider?->default_model,
            'status' => $this->queueStatus($s),
            'next_action' => $this->queueAction($s),
            'scheduled_for' => optional($s->next_run_at)?->toIso8601String(),
        ]);

        $promptWaiting = BulletinPromptRun::query()->with('bulletinType:id,name')->where('status', 'waiting_ai_response')->latest()->limit(8)->get()
            ->map(fn ($r) => ['type' => 'prompt', 'id' => 'prompt-'.$r->id, 'bulletin' => $r->bulletinType?->name, 'bulletin_id' => $r->bulletin_type_id, 'status' => 'waiting_ai_response', 'next_action' => 'generate_ai_response', 'scheduled_for' => optional($r->updated_at)?->toIso8601String()]);

        $scriptsPending = Script::query()->with('bulletinPromptRun.bulletinType:id,name')->where('review_status', 'pending')->where('status', '!=', 'archived')->latest()->limit(8)->get()
            ->map(fn ($s) => ['type' => 'script', 'id' => 'script-'.$s->id, 'bulletin' => $s->bulletinPromptRun?->bulletinType?->name, 'bulletin_id' => $s->bulletinPromptRun?->bulletin_type_id, 'status' => 'script_pending_review', 'next_action' => 'review_script', 'scheduled_for' => optional($s->updated_at)?->toIso8601String()]);

        $sourcesPending = SourceReference::query()
            ->with([
                'script:id,title,bulletin_prompt_run_id',
                'script.bulletinPromptRun:id,bulletin_type_id',
                'script.bulletinPromptRun.bulletinType:id,name',
                'bulletinPromptRun:id,bulletin_type_id',
                'bulletinPromptRun.bulletinType:id,name',
            ])
            ->withoutArchived()
            ->where('verification_status', 'pending')
            ->latest()
            ->limit(8)
            ->get()
            ->map(function (SourceReference $sourceReference) {
                $bulletinType = $sourceReference->bulletinPromptRun?->bulletinType
                    ?? $sourceReference->script?->bulletinPromptRun?->bulletinType;

                return [
                    'type' => 'source',
                    'id' => 'source-'.$sourceReference->id,
                    'bulletin' => $bulletinType?->name,
                    'bulletin_id' => $bulletinType?->id,
                    'status' => 'sources_pending_verification',
                    'next_action' => 'verify_sources',
                    'scheduled_for' => optional($sourceReference->updated_at)?->toIso8601String(),
                ];
            });
        $readyScripts = Script::query()->with('bulletinPromptRun.bulletinType:id,name')->where('status', '!=', 'archived')->where('production_status', 'ready_for_production')->latest()->limit(6)->get()
            ->map(fn ($s) => ['type' => 'production', 'id' => 'production-'.$s->id, 'bulletin' => $s->bulletinPromptRun?->bulletinType?->name, 'bulletin_id' => $s->bulletinPromptRun?->bulletin_type_id, 'status' => 'ready_for_production', 'next_action' => 'prepare_production', 'scheduled_for' => optional($s->updated_at)?->toIso8601String()]);

        return $scheduleItems->concat($promptWaiting)->concat($scriptsPending)->concat($sourcesPending)->concat($readyScripts)->values();
    }

    private function buildScheduledBulletins(Collection $schedules): array
    {
        $groups = ['on' => [], 'off' => [], 'incomplete' => [], 'attention' => []];
        foreach ($schedules as $s) {
            $item = [
                'id' => $s->id,
                'bulletin' => $s->bulletinType?->name ?? $s->name,
                'location' => $s->location?->name,
                'category' => $s->newsCategory?->name,
                'provider' => $s->bulletinType?->aiProvider?->name,
                'model' => $s->bulletinType?->aiProvider?->default_model,
                'frequency' => $s->run_frequency,
                'time' => $s->run_time ?: $s->scheduled_time,
                'next_run' => optional($s->next_run_at)?->toIso8601String(),
                'last_run' => optional($s->last_run_at ?: $s->runs->first()?->scheduled_for)?->toIso8601String(),
                'last_result' => $s->runs->first()?->status,
                'is_on' => (bool) $s->is_active,
            ];

            if (! $s->bulletinType || ! $s->location || ! $s->newsCategory || ! $s->run_frequency || ! ($s->run_time ?: $s->scheduled_time)) $groups['incomplete'][] = $item;
            elseif (! $s->is_active || ! $s->bulletinType->is_active) $groups['off'][] = $item;
            elseif ($this->needsAttention($s) || $this->hasFailedRunToday((int) $s->bulletin_type_id)) $groups['attention'][] = $item;
            else $groups['on'][] = $item;
        }
        return $groups;
    }

    private function buildMapOverview(Collection $schedules): array
    {
        return [
            'locations' => $schedules->groupBy(fn ($s) => $s->location?->name ?: 'Sin ubicación')->map(function ($rows, $location) {
                return [
                    'location' => $location,
                    'active_bulletins' => $rows->filter(fn ($s) => $s->is_active && $s->bulletinType?->is_active)->count(),
                    'pending_tasks' => $rows->filter(fn ($s) => $s->next_run_at && $s->next_run_at->isPast() && $s->is_active)->count(),
                    'failed' => $rows->filter(fn ($s) => $this->hasFailedRunToday((int) $s->bulletin_type_id))->count(),
                    'missing_provider' => $rows->filter(fn ($s) => ! $s->bulletinType?->ai_provider_id)->count(),
                ];
            })->values(),
            'mapRoute' => route('editor.world-map.index'),
        ];
    }

    private function buildCoverageOverview(Collection $schedules): array
    {
        return [
            'explanation' => 'dashboard.coverage.explanation',
            'groups' => $schedules->groupBy(fn ($s) => ($s->location?->name ?: 'Sin ubicación').'|'.($s->newsCategory?->name ?: 'Sin categoría'))->map(function ($rows, $key) {
                [$location, $category] = explode('|', $key);
                return ['location' => $location, 'category' => $category, 'active' => $rows->where('is_active', true)->count(), 'paused' => $rows->where('is_active', false)->count(), 'missing_configuration' => $rows->filter(fn ($s) => ! $s->bulletinType?->ai_provider_id || ! $s->run_frequency)->count()];
            })->values(),
        ];
    }

    private function buildAiProviderStatus(\Carbon\Carbon $today, Collection $bulletins): Collection { return AiProvider::query()->where('is_testing', false)->get(['id','name','default_model','is_active','supports_grounding','provider_category','rate_limited_until'])->map(fn (AiProvider $p) => ['id'=>$p->id,'name'=>$p->name,'model'=>$p->default_model,'purpose'=>$p->provider_category,'is_active'=>$p->is_active,'grounded'=>(bool)$p->supports_grounding,'usage_today'=>EditorialScheduleRun::query()->whereDate('scheduled_for',$today->toDateString())->whereHas('schedule.bulletinType', fn($q)=>$q->where('ai_provider_id',$p->id))->count(),'availability'=>$p->rate_limited_until && $p->rate_limited_until->isFuture() ? 'rate_limited' : 'available','bulletins'=>$bulletins->where('ai_provider_id',$p->id)->pluck('name')->values()])->values(); }

    private function buildLatestExecutions(): Collection
    {
        return EditorialScheduleRun::query()->with(['schedule.bulletinType.aiProvider:id,name,default_model','script:id,title,production_status','sourceReferences:id,editorial_schedule_run_id,verification_status'])->latest('scheduled_for')->limit(12)->get()->map(fn ($run) => [
            'id' => $run->id,
            'scheduled_for' => optional($run->scheduled_for)?->toIso8601String(),
            'bulletin' => $run->schedule?->bulletinType?->name ?? $run->schedule?->name,
            'status' => $run->status,
            'provider' => $run->schedule?->bulletinType?->aiProvider?->name,
            'model' => $run->schedule?->bulletinType?->aiProvider?->default_model,
            'script' => $run->script ? ['id' => $run->script->id, 'title' => $run->script->title, 'production_status' => $run->script->production_status] : null,
            'sources_pending' => $run->sourceReferences->where('verification_status', 'pending')->count(),
            'sources_status' => $run->sourceReferences->where('verification_status', 'pending')->count() > 0 ? 'sources_pending_verification' : 'sources_verified',
            'next_action' => $run->status === 'failed' ? 'retry' : ($run->script ? 'review_script' : 'view_run'),
        ]);
    }

    private function buildEditorialAlerts(Collection $schedules, \Carbon\Carbon $today): Collection
    {
        $alerts = collect();
        if ($schedules->contains(fn ($s) => $s->is_active && $s->next_run_at && $s->next_run_at->isPast())) $alerts->push(['level' => 'warning', 'message' => 'schedule_overdue']);
        if ($schedules->contains(fn ($s) => $s->is_active && ! $s->bulletinType?->ai_provider_id)) $alerts->push(['level' => 'warning', 'message' => 'missing_ai_provider']);
        if (EditorialScheduleRun::query()->whereDate('scheduled_for', $today->toDateString())->where('status', 'failed')->exists()) $alerts->push(['level' => 'danger', 'message' => 'failed_today']);
        return $alerts;
    }

    private function hasFailedRunToday(int $bulletinId): bool { return EditorialScheduleRun::query()->whereHas('schedule', fn ($q) => $q->where('bulletin_type_id', $bulletinId))->whereDate('scheduled_for', now()->toDateString())->where('status', 'failed')->exists(); }
    private function needsAttention(EditorialSchedule $s): bool { return ! $s->bulletinType?->ai_provider_id || ! $s->run_frequency || ! ($s->run_time ?: $s->scheduled_time); }
    private function queueStatus(EditorialSchedule $s): string { if ($this->hasFailedRunToday((int) $s->bulletin_type_id)) return 'failed'; if ($this->needsAttention($s)) return 'incomplete'; if (! $s->is_active || ! $s->bulletinType?->is_active) return 'off'; if ($s->next_run_at && $s->next_run_at->isPast()) return 'overdue'; return 'due_today'; }
    private function queueAction(EditorialSchedule $s): string { return match ($this->queueStatus($s)) { 'failed' => 'retry', 'overdue' => 'run_now', 'incomplete' => 'configure', 'off' => 'configure', default => 'monitor' }; }
}
