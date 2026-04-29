<?php
namespace App\Services\Automation;

use App\Models\AiRequestLog;
use App\Models\BulletinPromptRun;
use App\Models\BulletinType;
use App\Models\EditorialSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AutomationStatusService { /* trimmed */
public function getBulletinAutomationOverview(): Collection { return BulletinType::query()->with(['language:id,name,code','location:id,name','newsCategory:id,name','schedules'=>fn($q)=>$q->orderBy('run_time')])->get()->map(fn(BulletinType $b)=>['id'=>$b->id,'name'=>$b->name,'language'=>$b->language?->name,'location'=>$b->location?->name,'category'=>$b->newsCategory?->name,'edition_type'=>$b->edition_type,'target_duration_seconds'=>$b->target_duration_seconds,'active_schedules_count'=>$b->schedules->where('is_active',true)->count(),'total_schedules_count'=>$b->schedules->count(),'schedules'=>$b->schedules->map(fn(EditorialSchedule $s)=>$this->getScheduleStatus($s))->values(),'latest_execution'=>$this->getLatestExecutionSummary($b)]); }
public function getScheduleStatus(EditorialSchedule $s): array { $overdue=$s->is_active&&$s->next_run_at&&Carbon::parse($s->next_run_at)->isPast(); return ['id'=>$s->id,'is_active'=>(bool)$s->is_active,'frequency'=>$s->run_frequency?:$s->frequency_type,'run_time'=>$s->run_time?:$s->scheduled_time,'run_days'=>$s->run_days?:$s->weekdays,'timezone'=>$s->timezone,'next_run_at'=>optional($s->next_run_at)?->toIso8601String(),'last_run_at'=>optional($s->last_run_at)?->toIso8601String(),'last_success_at'=>optional($s->last_success_at)?->toIso8601String(),'last_failure_at'=>optional($s->last_failure_at)?->toIso8601String(),'last_error_message'=>$s->last_error_message,'overdue'=>$overdue,'needs_manual_attention'=>(bool)($s->last_failure_at||$s->last_error_message||($s->is_active&&!$s->next_run_at)||$overdue),'flags'=>['auto_create_prompt_run'=>(bool)$s->auto_create_prompt_run,'auto_generate_prompt'=>(bool)$s->auto_generate_prompt,'auto_run_pipeline'=>(bool)$s->auto_run_pipeline,'auto_generate_ai_response'=>(bool)$s->auto_generate_ai_response,'auto_create_script'=>(bool)$s->auto_create_script,'auto_generate_metadata'=>(bool)$s->auto_generate_metadata,'auto_extract_sources'=>(bool)$s->auto_extract_sources]]; }
public function getLatestExecutionSummary(BulletinType $b): array { $p=BulletinPromptRun::query()->where('bulletin_type_id',$b->id)->latest()->first(); $script=$p?->script; $ai=$p?AiRequestLog::query()->where('bulletin_prompt_run_id',$p->id)->latest()->first():null; return ['latest_prompt_run_id'=>$p?->id,'latest_prompt_run_status'=>$p?->status,'latest_script_id'=>$script?->id,'latest_script_title'=>$script?->title,'latest_ai_request_status'=>$ai?->status]; }
}
