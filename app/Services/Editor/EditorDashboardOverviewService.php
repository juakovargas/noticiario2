<?php

namespace App\Services\Editor;

use App\Models\AiProvider;
use App\Models\EditorialSchedule;
use App\Models\EditorialScheduleRun;
use App\Models\Script;
use App\Models\SourceReference;
use App\Models\BulletinType;
use Illuminate\Support\Collection;

class EditorDashboardOverviewService
{
    public function build(): array
    {
        $today = now()->toDateString();

        $bulletins = BulletinType::query()->with([
            'location:id,name', 'newsCategory:id,name',
            'aiProvider:id,name,is_active,default_model,supports_grounding,provider_category,rate_limited_until',
            'primarySchedule:id,bulletin_type_id,is_active,next_run_at,auto_run_pipeline,last_run_at,run_frequency',
        ])->get();

        $schedules = EditorialSchedule::query()->with([
            'bulletinType:id,name,location_id,news_category_id,is_active,ai_provider_id',
            'bulletinType.aiProvider:id,name,is_active,default_model,supports_grounding,provider_category',
            'location:id,name', 'newsCategory:id,name',
        ])->get();

        return [
            'summary' => $this->buildSummary($schedules, $today),
            'mapOverview' => $this->buildMapOverview($schedules),
            'aiProviderStatus' => $this->buildAiProviderStatus($today, $bulletins),
            'workQueue' => $this->buildWorkQueue($schedules),
            'bulletinGroups' => $this->buildBulletinGroups($bulletins),
            'coverage' => $this->buildCoverage($bulletins, $schedules),
            'latestExecutions' => $this->buildLatestExecutions(),
            'editorialAlerts' => $this->buildEditorialAlerts($schedules, $today),
        ];
    }

    private function buildSummary(Collection $schedules, string $today): array { return ['activeBulletins'=>BulletinType::query()->where('is_active',true)->count(),'pendingTasks'=>$this->buildWorkQueue($schedules)->count(),'overdueSchedules'=>$schedules->where('is_active',true)->filter(fn($s)=>$s->next_run_at && $s->next_run_at->isPast())->count(),'failedRunsToday'=>EditorialScheduleRun::query()->where('status','failed')->whereDate('scheduled_for',$today)->count(),'scriptsPendingReview'=>Script::query()->where('review_status','pending')->where('status','!=','archived')->count(),'sourcesPendingVerification'=>SourceReference::query()->withoutArchived()->where('verification_status','pending')->count(),'readyForProduction'=>Script::query()->where('status','!=','archived')->where('production_status','ready_for_production')->count()]; }

    private function buildMapOverview(Collection $schedules): array
    {
        return [
            'activeLocations' => $schedules->where('is_active', true)->pluck('location.name')->filter()->unique()->count(),
            'inactiveLocations' => $schedules->where('is_active', false)->pluck('location.name')->filter()->unique()->count(),
            'locationsMissingProvider' => $schedules->filter(fn($s) => ! $s->bulletinType?->ai_provider_id)->pluck('location.name')->filter()->unique()->count(),
            'locationsPendingTasks' => $schedules->filter(fn($s) => $s->is_active && $s->next_run_at && $s->next_run_at->isPast())->pluck('location.name')->filter()->unique()->count(),
            'locationsWithFailures' => $schedules->filter(fn($s) => $s->bulletinType_id && $this->bulletinHasFailedRunToday((int)$s->bulletin_type_id))->pluck('location.name')->filter()->unique()->count(),
            'mapRoute' => route('editor.world-map.index'),
        ];
    }

    private function buildWorkQueue(Collection $schedules): Collection
    {
        return $schedules->map(function ($schedule) {
            $provider = $schedule->bulletinType?->aiProvider;
            $status = ! $schedule->is_active ? 'paused' : (($schedule->next_run_at && $schedule->next_run_at->isPast()) ? 'overdue' : 'due_today');
            if ($this->bulletinHasFailedRunToday((int) $schedule->bulletin_type_id)) $status = 'failed';
            if (! $schedule->bulletinType?->ai_provider_id) $status = 'incomplete';

            return ['id'=>$schedule->id,'priority'=>$status,'scheduled_for'=>optional($schedule->next_run_at)?->toIso8601String(),'bulletin'=>$schedule->bulletinType?->name ?? $schedule->name,'bulletin_id'=>$schedule->bulletinType?->id,'location'=>$schedule->location?->name,'category'=>$schedule->newsCategory?->name,'provider'=>$provider?->name ?? 'missing_ai_provider','model'=>$provider?->default_model,'grounded'=>$provider?->supports_grounding ?? false,'automation'=>$schedule->auto_run_pipeline ? 'automatic' : 'manual','pipeline_state'=>$schedule->auto_run_pipeline ? 'ready' : 'manual_required','next_action'=>$status==='failed'?'retry':($status==='overdue'?'run_now':'configure'),'schedule_id'=>$schedule->id];
        })->sortBy(fn($row)=>match($row['priority']){'failed'=>0,'overdue'=>1,'due_today'=>2,'incomplete'=>3,default=>4})->values();
    }

    private function buildBulletinGroups(Collection $bulletins): array
    {
        $groups = ['active' => collect(), 'paused' => collect(), 'incomplete' => collect(), 'attention' => collect()];
        foreach ($bulletins as $b) {
            $target = 'active'; $reason = null;
            if (! $b->is_active) [$target, $reason] = ['paused', 'disabled'];
            elseif (! $b->location_id || ! $b->news_category_id) [$target, $reason] = ['incomplete', 'missing_location'];
            elseif (! $b->primarySchedule) [$target, $reason] = ['incomplete', 'missing_schedule'];
            elseif (! $b->primarySchedule->is_active) [$target, $reason] = ['paused', 'inactive_schedule'];
            elseif (! $b->ai_provider_id) [$target, $reason] = ['incomplete', 'missing_ai_provider'];
            elseif (! $b->primarySchedule->next_run_at) [$target, $reason] = ['incomplete', 'missing_next_run'];
            elseif ($this->bulletinHasFailedRunToday($b->id)) [$target, $reason] = ['attention', 'last_execution_failed'];

            $groups[$target]->push(['id'=>$b->id,'name'=>$b->name,'location'=>$b->location?->name,'category'=>$b->newsCategory?->name,'provider'=>$b->aiProvider?->name,'model'=>$b->aiProvider?->default_model,'next_run'=>optional($b->primarySchedule?->next_run_at)?->toIso8601String(),'reason'=>$reason]);
        }
        return collect($groups)->map(fn($group) => $group->values())->all();
    }

    private function buildCoverage(Collection $bulletins, Collection $schedules): array
    {
        $locations = $bulletins->pluck('location.name')->filter()->unique()->values();
        $categories = $bulletins->pluck('newsCategory.name')->filter()->unique()->values();
        $cells = [];
        foreach ($locations as $location) foreach ($categories as $category) {
            $bulletin = $bulletins->first(fn($b)=>$b->location?->name===$location && $b->newsCategory?->name===$category);
            $state = 'not_configured';
            if ($bulletin) {
                $schedule = $schedules->first(fn($s)=>$s->bulletin_type_id===$bulletin->id);
                $state = ! $bulletin->is_active ? 'disabled' : (! $bulletin->ai_provider_id ? 'missing_ai_provider' : 'covered');
                if (! $schedule) $state = 'missing_schedule';
                if ($this->bulletinHasFailedRunToday($bulletin->id)) $state = 'failed';
            }
            $cells[] = compact('location', 'category', 'state');
        }
        return ['explanation'=>'coverage_editorial_explanation','locations'=>$locations,'categories'=>$categories,'cells'=>$cells];
    }

    private function buildAiProviderStatus(string $today, Collection $bulletins): Collection
    {
        return AiProvider::query()->where('is_testing', false)->get(['id','name','default_model','is_active','supports_grounding','provider_category','rate_limited_until'])
            ->map(function (AiProvider $provider) use ($today, $bulletins) {
                $usedBy = $bulletins->where('ai_provider_id', $provider->id)->pluck('name')->values();
                return ['id'=>$provider->id,'name'=>$provider->name,'model'=>$provider->default_model,'purpose'=>$provider->provider_category,'is_active'=>$provider->is_active,'grounded'=>(bool)$provider->supports_grounding,'usage_today'=>EditorialScheduleRun::query()->whereDate('scheduled_for',$today)->whereHas('schedule.bulletinType', fn($q)=>$q->where('ai_provider_id',$provider->id))->count(),'availability'=>$provider->rate_limited_until && $provider->rate_limited_until->isFuture() ? 'rate_limited' : 'available','bulletins'=>$usedBy];
            })->values();
    }

    private function buildLatestExecutions(): Collection
    {
        return EditorialScheduleRun::query()->with(['schedule.bulletinType.aiProvider:id,name,default_model','bulletinPromptRun:id,editorial_schedule_run_id','script:id,title,production_status','sourceReferences:id,editorial_schedule_run_id,verification_status'])
            ->latest('scheduled_for')->limit(12)->get()->map(fn($run)=>['id'=>$run->id,'scheduled_for'=>optional($run->scheduled_for)?->toIso8601String(),'bulletin'=>$run->schedule?->bulletinType?->name ?? $run->schedule?->name,'bulletin_id'=>$run->schedule?->bulletinType?->id,'status'=>$run->status,'provider'=>$run->schedule?->bulletinType?->aiProvider?->name,'model'=>$run->schedule?->bulletinType?->aiProvider?->default_model,'prompt_run_id'=>$run->bulletinPromptRun?->id,'script'=>$run->script?['id'=>$run->script->id,'title'=>$run->script->title,'production_status'=>$run->script->production_status]:null,'sources_count'=>$run->sourceReferences->count(),'sources_pending'=>$run->sourceReferences->where('verification_status','pending')->count()]);
    }

    private function buildEditorialAlerts(Collection $schedules, string $today): Collection { $a=collect(); if($schedules->where('is_active',true)->filter(fn($s)=>$s->next_run_at&&$s->next_run_at->isPast())->isNotEmpty())$a->push(['level'=>'warning','message'=>'schedule_overdue']); if(BulletinType::query()->where('is_active',true)->whereNull('ai_provider_id')->exists())$a->push(['level'=>'warning','message'=>'missing_ai_provider']); if(EditorialScheduleRun::query()->where('status','failed')->whereDate('scheduled_for',$today)->exists())$a->push(['level'=>'danger','message'=>'failed_today']); return $a->values(); }
    private function bulletinHasFailedRunToday(int $bulletinId): bool { return EditorialScheduleRun::query()->whereHas('schedule',fn($q)=>$q->where('bulletin_type_id',$bulletinId))->where('status','failed')->whereDate('scheduled_for',now()->toDateString())->exists(); }
}
