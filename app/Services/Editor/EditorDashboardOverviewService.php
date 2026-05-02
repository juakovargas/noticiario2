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
        $today = now()->toDateString();
        $schedules = EditorialSchedule::query()->with(['bulletinType.aiProvider:id,name,is_active,default_model,supports_grounding,rate_limited_until', 'location:id,name', 'newsCategory:id,name'])->get();
        $bulletins = BulletinType::query()->with(['location:id,name', 'newsCategory:id,name', 'aiProvider:id,name,is_active,default_model,supports_grounding,rate_limited_until', 'primarySchedule:id,bulletin_type_id,is_active,next_run_at,run_frequency'])->get();

        return [
            'dailySummary' => $this->buildDailySummary($schedules, $today),
            'pendingTasks' => $this->buildPendingTasks($schedules),
            'coverageMatrix' => $this->buildCoverageMatrix($schedules, $bulletins),
            'bulletinGroups' => $this->buildBulletinGroups($bulletins),
            'editorialAlerts' => $this->buildEditorialAlerts($schedules, $today),
            'aiProviders' => $this->buildAiProviders($today),
            'latestRuns' => $this->buildLatestRuns(),
        ];
    }

    private function buildDailySummary(Collection $schedules, string $today): array { return ['activeBulletins'=>BulletinType::query()->where('is_active',true)->count(),'pendingTasks'=>$this->buildPendingTasks($schedules)->count(),'overdueSchedules'=>$schedules->where('is_active',true)->filter(fn($s)=>$s->next_run_at && $s->next_run_at->isPast())->count(),'failedRunsToday'=>EditorialScheduleRun::query()->where('status','failed')->whereDate('scheduled_for',$today)->count()+BulletinPromptRun::query()->where('status','failed')->whereDate('scheduled_for',$today)->count(),'scriptsPendingReview'=>Script::query()->where('review_status','pending')->where('status','!=','archived')->count(),'sourcesPendingVerification'=>SourceReference::query()->withoutArchived()->where('verification_status','pending')->count(),'readyForProduction'=>Script::query()->where('status','!=','archived')->where('production_status','ready_for_production')->count()]; }

    private function buildPendingTasks(Collection $schedules): Collection
    {
        return $schedules->map(function ($schedule) {
            $provider = $schedule->bulletinType?->aiProvider;
            $status = ! $schedule->is_active ? 'disabled' : (($schedule->next_run_at && $schedule->next_run_at->isPast()) ? 'overdue' : 'pending');
            if (! $schedule->bulletinType?->ai_provider_id) {
                $status = 'incomplete';
            }
            return ['id'=>$schedule->id,'scheduled_for'=>optional($schedule->next_run_at)?->toIso8601String(),'bulletin'=>$schedule->bulletinType?->name ?? $schedule->name,'bulletin_id'=>$schedule->bulletinType?->id,'location'=>$schedule->location?->name,'category'=>$schedule->newsCategory?->name,'provider'=>$provider?->name,'model'=>$provider?->default_model,'automation'=>$schedule->auto_run_pipeline ? 'automatic' : 'manual','pipeline_state'=>$schedule->auto_run_pipeline ? 'ready' : 'manual_required','status'=>$status,'last_run'=>optional($schedule->last_run_at)?->toIso8601String(),'next_action'=>$status==='overdue'?'run_now':($status==='incomplete'?'configure':'edit_schedule'),'schedule_id'=>$schedule->id];
        })->sortBy(fn($row)=>match($row['status']){'overdue'=>0,'incomplete'=>1,'pending'=>2,default=>3})->take(15)->values();
    }

    private function buildCoverageMatrix(Collection $schedules, Collection $bulletins): array { $locations=$bulletins->pluck('location.name')->filter()->unique()->take(8)->values(); $categories=$bulletins->pluck('newsCategory.name')->filter()->unique()->take(6)->values(); $cells=[]; foreach($locations as $location){foreach($categories as $category){$bulletin=$bulletins->first(fn($b)=>$b->location?->name===$location&&$b->newsCategory?->name===$category);$state='not_configured';if($bulletin){$state=!$bulletin->is_active?'disabled':(!$bulletin->ai_provider_id?'missing_provider':'active'); $schedule=$schedules->first(fn($s)=>$s->bulletin_type_id===$bulletin->id); if(!$schedule){$state='no_schedule';}elseif(!$schedule->is_active){$state='disabled';}elseif($this->bulletinHasFailedRunToday($bulletin->id)){$state='failed';}}$cells[]=['location'=>$location,'category'=>$category,'state'=>$state];}} return ['locations'=>$locations,'categories'=>$categories,'cells'=>$cells,'legend'=>['active','disabled','no_schedule','missing_provider','failed','not_configured']]; }

    private function buildBulletinGroups(Collection $bulletins): array
    {
        $active = collect(); $paused = collect(); $incomplete = collect(); $attention = collect();
        foreach ($bulletins as $b) {
            $reason = null;
            if (! $b->is_active) { $paused->push($this->mapBulletin($b, 'disabled')); continue; }
            if (! $b->location_id || ! $b->news_category_id) { $incomplete->push($this->mapBulletin($b, 'missing_location_category')); continue; }
            if (! $b->primarySchedule) { $incomplete->push($this->mapBulletin($b, 'missing_schedule')); continue; }
            if (! $b->primarySchedule->is_active) { $paused->push($this->mapBulletin($b, 'inactive_schedule')); continue; }
            if (! $b->ai_provider_id) { $incomplete->push($this->mapBulletin($b, 'missing_provider')); continue; }
            if (! $b->primarySchedule->next_run_at) { $incomplete->push($this->mapBulletin($b, 'missing_next_run')); continue; }
            if ($this->bulletinHasFailedRunToday($b->id)) { $attention->push($this->mapBulletin($b, 'last_execution_failed')); continue; }
            $active->push($this->mapBulletin($b, $reason));
        }

        return compact('active', 'paused', 'incomplete', 'attention');
    }

    private function mapBulletin(BulletinType $b, ?string $reason): array { return ['id'=>$b->id,'name'=>$b->name,'location'=>$b->location?->name,'category'=>$b->newsCategory?->name,'frequency'=>$b->primarySchedule?->run_frequency,'next_run'=>optional($b->primarySchedule?->next_run_at)?->toIso8601String(),'provider'=>$b->aiProvider?->name,'reason'=>$reason]; }
    private function bulletinHasFailedRunToday(int $bulletinId): bool { return EditorialScheduleRun::query()->whereHas('schedule',fn($q)=>$q->where('bulletin_type_id',$bulletinId))->where('status','failed')->whereDate('scheduled_for',now()->toDateString())->exists(); }

    private function buildEditorialAlerts(Collection $schedules, string $today): Collection { $a=collect(); if(AiProvider::query()->where('name','like','%gemini%')->where('is_active',true)->exists())$a->push(['level'=>'success','message'=>'gemini_configured']); if($schedules->where('is_active',true)->filter(fn($s)=>$s->next_run_at&&$s->next_run_at->isPast())->isNotEmpty())$a->push(['level'=>'warning','message'=>'schedule_overdue']); if(BulletinType::query()->where('is_active',true)->whereNull('ai_provider_id')->exists())$a->push(['level'=>'warning','message'=>'missing_provider']); if(EditorialScheduleRun::query()->where('status','failed')->whereDate('scheduled_for',$today)->exists())$a->push(['level'=>'danger','message'=>'failed_today']); return $a->take(8)->values(); }

    private function buildAiProviders(string $today): Collection
    {
        return AiProvider::query()->whereIn('name', ['Gemini Grounded', 'Groq'])->orWhere('provider_type', 'groq')->orWhere('provider_type', 'google_gemini')->get()->unique('id')->values()->map(function (AiProvider $provider) use ($today) {
            return ['id'=>$provider->id,'name'=>$provider->name,'model'=>$provider->default_model,'purpose'=>str_contains(strtolower($provider->name), 'gemini') ? 'main_grounded_news' : 'aux_rewrite_style','is_active'=>$provider->is_active,'grounded'=>(bool)$provider->supports_grounding,'usage_today'=>BulletinPromptRun::query()->whereDate('created_at',$today)->whereJsonContains('metadata->ai_provider', $provider->name)->count(),'availability'=>$provider->rate_limited_until && $provider->rate_limited_until->isFuture() ? 'rate_limited' : 'available'];
        });
    }

    private function buildLatestRuns(): Collection { return EditorialScheduleRun::query()->with(['schedule.bulletinType.aiProvider:id,name,default_model','bulletinPromptRun:id,editorial_schedule_run_id','script:id,title,production_status','sourceReferences:id,editorial_schedule_run_id'])->latest('scheduled_for')->limit(10)->get()->map(fn($run)=>['id'=>$run->id,'scheduled_for'=>optional($run->scheduled_for)?->toIso8601String(),'bulletin'=>$run->schedule?->bulletinType?->name ?? $run->schedule?->name,'bulletin_id'=>$run->schedule?->bulletinType?->id,'status'=>$run->status,'provider'=>$run->schedule?->bulletinType?->aiProvider?->name,'model'=>$run->schedule?->bulletinType?->aiProvider?->default_model,'prompt_run_id'=>$run->bulletinPromptRun?->id,'script'=>$run->script?['id'=>$run->script->id,'title'=>$run->script->title,'production_status'=>$run->script->production_status]:null,'sources_count'=>$run->sourceReferences->count(),'sources_pending'=>$run->sourceReferences->where('verification_status','pending')->count()]); }
}
