<?php

namespace App\Services\Editor;

use App\Models\AiProvider;
use App\Models\BulletinPromptRun;
use App\Models\BulletinType;
use App\Models\EditorialSchedule;
use App\Models\EditorialScheduleRun;
use App\Models\Script;
use App\Models\SourceReference;
use App\Services\Maps\BulletinMapService;
use Illuminate\Support\Collection;

class EditorDashboardOverviewService
{
    public function __construct(private readonly BulletinMapService $mapService) {}

    public function build(): array
    {
        $today = now();
        $schedules = EditorialSchedule::query()->with(['bulletinType.aiProvider:id,name,default_model,supports_grounding,is_active,provider_category,rate_limited_until','location:id,name','newsCategory:id,name','runs' => fn ($q) => $q->latest('scheduled_for')->limit(1)])->get();
        return [
            'headerActions' => [['key'=>'automation','label'=>'Ver automatización','href'=>route('editor.automation.index')],['key'=>'createBulletin','label'=>'Crear informativo','href'=>route('editor.bulletin-types.create')],['key'=>'fullMap','label'=>'Ver mapa completo','href'=>route('editor.world-map.index')]],
            'summaryCards' => $this->summaryCards($schedules, $today),
            'mapOverview' => ['markers'=>$this->mapService->getEditorMapData(),'mapRoute'=>route('editor.world-map.index')],
            'scheduledBulletins' => $this->scheduledBulletins($schedules),
            'actionableQueue' => $this->buildActionableQueue($schedules),
            'aiEngines' => $this->buildAiEngines($today),
            'coverageByLocationTopic' => $this->buildCoverageOverview($schedules),
            'latestExecutions' => $this->buildLatestExecutions(),
        ];
    }
    private function summaryCards(Collection $s, $today): array {return [['key'=>'active','label'=>'Informativos activos','value'=>$s->where('is_active',true)->count()],['key'=>'today','label'=>'Programados hoy','value'=>$s->filter(fn($x)=>$x->next_run_at?->isToday())->count()],['key'=>'overdue','label'=>'Vencidos','value'=>$s->filter(fn($x)=>$x->is_active && $x->next_run_at?->isPast())->count()],['key'=>'failed','label'=>'Fallidos hoy','value'=>EditorialScheduleRun::whereDate('scheduled_for',$today->toDateString())->where('status','failed')->count()],['key'=>'scriptsPending','label'=>'Guiones pendientes de revisión','value'=>Script::where('review_status','pending')->where('status','!=','archived')->count()],['key'=>'sourcesPending','label'=>'Fuentes pendientes de verificación','value'=>SourceReference::withoutArchived()->where('verification_status','pending')->count()],['key'=>'ready','label'=>'Listo para producción','value'=>Script::where('status','!=','archived')->where('production_status','ready_for_production')->count()]];}
    private function scheduledBulletins(Collection $schedules): array { return $schedules->map(fn($s)=>['id'=>$s->id,'is_on'=>(bool)$s->is_active,'bulletin'=>$s->bulletinType?->name ?? $s->name,'bulletin_url'=>$s->bulletinType?->id ? route('editor.bulletin-types.show',$s->bulletinType->id):route('editor.editorial-schedules.edit',$s->id),'location'=>$s->location?->name,'category'=>$s->newsCategory?->name,'provider'=>$s->bulletinType?->aiProvider?->name,'model'=>$s->bulletinType?->aiProvider?->default_model,'next_run'=>optional($s->next_run_at)?->toIso8601String(),'last_run'=>optional($s->last_run_at ?: $s->runs->first()?->scheduled_for)?->toIso8601String(),'last_result'=>$s->runs->first()?->status,'pipeline'=>['Prompt','IA','Guion','Fuentes','Revisión','Producción','Audio','Vídeo','Publicación'],'view_url'=>$s->bulletinType?->id ? route('editor.bulletin-types.show',$s->bulletinType->id):route('editor.editorial-schedules.edit',$s->id),'runs_url'=>route('editor.editorial-schedule-runs.index',['editorial_schedule_id'=>$s->id])])->values()->all(); }
    private function buildActionableQueue(Collection $schedules): array { $queue=[]; foreach($schedules as $s){ if(!$s->is_active && !$this->needsAttention($s)) continue; if(($s->next_run_at && $s->next_run_at->isPast())||$this->needsAttention($s)) $queue[]=['id'=>'schedule-'.$s->id,'type_label'=>'Horario','bulletin'=>$s->bulletinType?->name,'provider'=>$s->bulletinType?->aiProvider?->name,'model'=>$s->bulletinType?->aiProvider?->default_model,'status'=>$this->needsAttention($s)?'incomplete':'overdue','next_action'=>$this->needsAttention($s)?'configure':'run_now']; }
        return collect($queue)->concat(BulletinPromptRun::with('bulletinType.aiProvider')->where('status','waiting_ai_response')->latest()->limit(5)->get()->map(fn($r)=>['id'=>'prompt-'.$r->id,'type_label'=>'Prompt IA','bulletin'=>$r->bulletinType?->name,'provider'=>$r->bulletinType?->aiProvider?->name,'model'=>$r->bulletinType?->aiProvider?->default_model,'status'=>'waiting_ai_response','next_action'=>'generate_ai_response']))->values()->all(); }
    private function buildAiEngines($today): array { $bulletins=BulletinType::with('aiProvider:id,name,default_model')->get(); return AiProvider::where('is_testing',false)->get(['id','name','default_model','is_active','supports_grounding','rate_limited_until'])->map(fn($p)=>['id'=>$p->id,'name'=>$p->name,'model'=>$p->default_model,'grounded'=>(bool)$p->supports_grounding,'status'=>$p->is_active?'active':'inactive','bulletins'=>$bulletins->where('ai_provider_id',$p->id)->pluck('name')->values()->all()])->values()->all(); }
    private function buildCoverageOverview(Collection $s): array { return ['groups'=>$s->groupBy(fn($x)=>($x->location?->name ?: 'Sin ubicación').'|'.($x->newsCategory?->name ?: 'Sin categoría'))->map(function($rows,$key){[$l,$c]=explode('|',$key); return ['location'=>$l,'category'=>$c,'active'=>$rows->where('is_active',true)->count(),'paused'=>$rows->where('is_active',false)->count(),'missing_provider'=>$rows->filter(fn($r)=>!$r->bulletinType?->ai_provider_id)->count(),'missing_schedule'=>$rows->filter(fn($r)=>!$r->run_frequency || !($r->run_time ?: $r->scheduled_time))->count()];})->values()->all()]; }
    private function buildLatestExecutions(): array { return EditorialScheduleRun::with(['schedule.bulletinType.aiProvider:id,name,default_model','sourceReferences:id,editorial_schedule_run_id,verification_status'])->latest('scheduled_for')->limit(10)->get()->map(fn($r)=>['id'=>$r->id,'scheduled_for'=>optional($r->scheduled_for)->toIso8601String(),'bulletin'=>$r->schedule?->bulletinType?->name ?? $r->schedule?->name,'provider'=>$r->schedule?->bulletinType?->aiProvider?->name,'model'=>$r->schedule?->bulletinType?->aiProvider?->default_model,'status'=>$r->status,'pipeline'=>['Prompt','IA','Guion','Fuentes','Revisión','Producción','Audio','Vídeo','Publicación']])->values()->all(); }
    private function needsAttention(EditorialSchedule $s): bool { return ! $s->bulletinType?->ai_provider_id || ! $s->run_frequency || ! ($s->run_time ?: $s->scheduled_time); }
}
