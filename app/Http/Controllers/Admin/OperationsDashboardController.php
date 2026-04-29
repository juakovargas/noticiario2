<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller; use App\Models\{AiRequestLog,BackgroundTask,BulletinPromptRun,EditorialSchedule,EditorialScheduleRun,OperationalEvent,WorkerHeartbeat}; use Inertia\Inertia; use Inertia\Response;
class OperationsDashboardController extends Controller {
 public function index(): Response { return Inertia::render('Admin/Operations/Index', $this->summary()); }
 public function summaryData(){ return response()->json($this->summary()); }
 private function summary(): array { return [
  'summary'=>['runs_today'=>EditorialScheduleRun::whereDate('created_at',today())->count(),'prompt_runs_today'=>BulletinPromptRun::whereDate('created_at',today())->count(),'ai_requests_today'=>AiRequestLog::whereDate('created_at',today())->count(),'ai_failures_today'=>AiRequestLog::where('status','failed')->whereDate('created_at',today())->count(),'background_running'=>BackgroundTask::running()->count(),'background_failed_today'=>BackgroundTask::failed()->whereDate('failed_at',today())->count(),'failed_jobs_today'=>\Illuminate\Support\Facades\Schema::hasTable('failed_jobs') ? \Illuminate\Support\Facades\DB::table('failed_jobs')->whereDate('failed_at',today())->count() : 0,'overdue_schedules'=>EditorialSchedule::where('is_active',true)->where('next_run_at','<',now()->subMinutes(15))->count(),'worker_healthy'=>WorkerHeartbeat::where('last_seen_at','>',now()->subMinutes(5))->exists(),'estimated_ai_cost_today'=>(float) AiRequestLog::whereDate('created_at',today())->sum('estimated_cost'),'estimated_ai_cost_month'=>(float) AiRequestLog::whereBetween('created_at',[now()->startOfMonth(),now()->endOfMonth()])->sum('estimated_cost')],
  'recentEvents'=>OperationalEvent::recent()->limit(20)->get(),
 ]; }
}
