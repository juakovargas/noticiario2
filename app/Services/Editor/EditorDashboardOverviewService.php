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
        $schedules = EditorialSchedule::query()->with(['bulletinType.aiProvider:id,name,is_active', 'location:id,name', 'newsCategory:id,name'])->get();
        $bulletins = BulletinType::query()->with(['location:id,name', 'newsCategory:id,name', 'aiProvider:id,name,is_active', 'primarySchedule:id,bulletin_type_id,is_active,next_run_at,run_frequency'])->get();

        return [
            'dailySummary' => $this->buildDailySummary($schedules, $today),
            'pendingTasks' => $this->buildPendingTasks($schedules),
            'coverageMatrix' => $this->buildCoverageMatrix($schedules, $bulletins),
            'activeBulletins' => $this->buildActiveBulletins($bulletins),
            'inactiveBulletins' => $this->buildInactiveBulletins($bulletins),
            'editorialAlerts' => $this->buildEditorialAlerts($schedules, $today),
            'latestRuns' => $this->buildLatestRuns(),
        ];
    }

    private function buildDailySummary(Collection $schedules, string $today): array { return ['activeBulletins'=>BulletinType::query()->where('is_active',true)->count(),'pendingTasks'=>$this->buildPendingTasks($schedules)->count(),'overdueSchedules'=>$schedules->where('is_active',true)->filter(fn($s)=>$s->next_run_at && $s->next_run_at->isPast())->count(),'failedRunsToday'=>EditorialScheduleRun::query()->where('status','failed')->whereDate('scheduled_for',$today)->count()+BulletinPromptRun::query()->where('status','failed')->whereDate('scheduled_for',$today)->count(),'scriptsPendingReview'=>Script::query()->where('review_status','pending')->where('status','!=','archived')->count(),'sourcesPendingVerification'=>SourceReference::query()->withoutArchived()->where('verification_status','pending')->count(),'readyForProduction'=>Script::query()->where('status','!=','archived')->where('production_status','ready_for_production')->count()]; }

    private function buildPendingTasks(Collection $schedules): Collection { return $schedules->map(function ($schedule) { $status = ! $schedule->is_active ? 'disabled' : (($schedule->next_run_at && $schedule->next_run_at->isPast()) ? 'overdue' : 'pending'); if ($schedule->bulletinType && ! $schedule->bulletinType->ai_provider_id) { $status = 'incomplete'; } return ['id'=>$schedule->id,'scheduled_for'=>optional($schedule->next_run_at)?->toIso8601String(),'bulletin'=>$schedule->bulletinType?->name ?? $schedule->name,'location'=>$schedule->location?->name,'category'=>$schedule->newsCategory?->name,'provider'=>$schedule->bulletinType?->aiProvider?->name,'automation'=>$schedule->auto_run_pipeline ? 'automatic' : 'manual','status'=>$status,'last_run'=>optional($schedule->last_run_at)?->toIso8601String(),'next_action'=>$status==='overdue'?'run_now':($status==='incomplete'?'configure':'edit_schedule'),'schedule_id'=>$schedule->id]; })->sortBy(fn($row)=>match($row['status']){'overdue'=>0,'failed'=>1,'pending'=>2,default=>3})->take(15)->values(); }

    private function buildCoverageMatrix(Collection $schedules, Collection $bulletins): array { $locations=$bulletins->pluck('location.name')->filter()->unique()->take(8)->values(); $categories=$bulletins->pluck('newsCategory.name')->filter()->unique()->take(6)->values(); $cells=[]; foreach($locations as $location){foreach($categories as $category){$bulletin=$bulletins->first(fn($b)=>$b->location?->name===$location&&$b->newsCategory?->name===$category);$state='not_configured';if($bulletin){$state=!$bulletin->is_active?'disabled':(!$bulletin->ai_provider_id?'missing_provider':'active'); if(!$schedules->contains(fn($s)=>$s->bulletin_type_id===$bulletin->id)){$state='no_schedule';}}$cells[]=['location'=>$location,'category'=>$category,'state'=>$state];}} return ['locations'=>$locations,'categories'=>$categories,'cells'=>$cells]; }

    private function buildActiveBulletins(Collection $bulletins): Collection { return $bulletins->filter(fn($b)=>$b->is_active)->map(fn($b)=>['id'=>$b->id,'name'=>$b->name,'location'=>$b->location?->name,'category'=>$b->newsCategory?->name,'frequency'=>$b->primarySchedule?->run_frequency,'next_run'=>optional($b->primarySchedule?->next_run_at)?->toIso8601String(),'provider'=>$b->aiProvider?->name])->values(); }
    private function buildInactiveBulletins(Collection $bulletins): Collection { return $bulletins->filter(fn($b)=>!$b->is_active||!$b->primarySchedule||!$b->ai_provider_id)->map(fn($b)=>['id'=>$b->id,'name'=>$b->name,'location'=>$b->location?->name,'category'=>$b->newsCategory?->name,'next_run'=>optional($b->primarySchedule?->next_run_at)?->toIso8601String(),'provider'=>$b->aiProvider?->name,'reason'=>!$b->is_active?'disabled':(!$b->ai_provider_id?'missing_provider':'missing_schedule')])->values(); }
    private function buildEditorialAlerts(Collection $schedules, string $today): Collection { $a=collect(); if(AiProvider::query()->where('name','like','%gemini%')->where('is_active',true)->exists())$a->push(['level'=>'success','message'=>'gemini_configured']); if($schedules->where('is_active',true)->filter(fn($s)=>$s->next_run_at&&$s->next_run_at->isPast())->isNotEmpty())$a->push(['level'=>'warning','message'=>'schedule_overdue']); if(BulletinType::query()->where('is_active',true)->whereNull('ai_provider_id')->exists())$a->push(['level'=>'warning','message'=>'missing_provider']); if(EditorialScheduleRun::query()->where('status','failed')->whereDate('scheduled_for',$today)->exists())$a->push(['level'=>'danger','message'=>'failed_today']); return $a->take(8)->values(); }
    private function buildLatestRuns(): Collection { return EditorialScheduleRun::query()->with(['schedule.bulletinType.aiProvider:id,name','bulletinPromptRun:id,editorial_schedule_run_id','script:id,title'])->latest('scheduled_for')->limit(10)->get()->map(fn($run)=>['id'=>$run->id,'scheduled_for'=>optional($run->scheduled_for)?->toIso8601String(),'bulletin'=>$run->schedule?->bulletinType?->name ?? $run->schedule?->name,'status'=>$run->status,'provider'=>$run->schedule?->bulletinType?->aiProvider?->name,'prompt_run_id'=>$run->bulletinPromptRun?->id,'script'=>$run->script?['id'=>$run->script->id,'title'=>$run->script->title]:null]); }
}
