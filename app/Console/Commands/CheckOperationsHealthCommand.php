<?php
namespace App\Console\Commands;
use App\Models\{AiRequestLog,BackgroundTask,EditorialSchedule,OperationAlertSetting,OperationalEvent,WorkerHeartbeat}; use App\Notifications\OperationAlertNotification; use App\Services\Operations\OperationalEventService; use Illuminate\Console\Command; use Illuminate\Support\Facades\DB; use Illuminate\Support\Facades\Notification;
class CheckOperationsHealthCommand extends Command { protected $signature='noticiario:check-operations-health {--send-alerts} {--dry-run}'; protected $description='Check operations health.';
public function handle(OperationalEventService $events): int { $issues=[]; if(BackgroundTask::query()->running()->where('started_at','<',now()->subMinutes(30))->exists()) $issues[]=['type'=>'background_task_failed','title'=>'Stalled background tasks detected'];
if(EditorialSchedule::query()->where('is_active',true)->whereNotNull('next_run_at')->where('next_run_at','<',now()->subMinutes(15))->exists()) $issues[]=['type'=>'schedule_overdue','title'=>'Overdue schedules detected'];
if(class_exists(DB::class) && DB::getSchemaBuilder()->hasTable('failed_jobs') && DB::table('failed_jobs')->whereDate('failed_at',today())->count()>0) $issues[]=['type'=>'failed_jobs_detected','title'=>'Failed queue jobs detected'];
if(AiRequestLog::query()->where('status','failed')->whereDate('created_at',today())->count()>0) $issues[]=['type'=>'ai_provider_failures','title'=>'AI request failures detected'];
if(WorkerHeartbeat::query()->where('last_seen_at','<',now()->subMinutes(5))->exists() || WorkerHeartbeat::query()->count()===0) $issues[]=['type'=>'worker_stalled','title'=>'Worker heartbeat missing or stale'];
foreach($issues as $issue){ if(!$this->option('dry-run')) $events->warning($issue['type'],$issue['title']); $this->warn($issue['title']); }
if($this->option('send-alerts') && !$this->option('dry-run')){ foreach(OperationAlertSetting::query()->where('is_active',true)->get() as $setting){ $issue=collect($issues)->firstWhere('type',$setting->alert_type); if(!$issue) continue;
$recent=OperationalEvent::query()->where('event_type','alert_sent')->where('status',$setting->alert_type)->where('created_at','>',now()->subMinutes($setting->cooldown_minutes))->exists(); if($recent) continue;
$recipients=array_filter($setting->email_recipients ?? []); if($recipients){ Notification::route('mail',$recipients)->notify(new OperationAlertNotification($issue['title'],'warning',$issue['title'])); $events->info('alert_sent','Operation alert sent',['status'=>$setting->alert_type]); }
 }} return self::SUCCESS; }}
