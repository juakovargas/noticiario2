<?php
namespace App\Console\Commands;
use App\Models\WorkerHeartbeat; use Illuminate\Console\Command;
class WorkerHeartbeatCommand extends Command { protected $signature='noticiario:worker-heartbeat {--worker=} {--queue=default} {--connection=}'; protected $description='Record queue worker heartbeat.';
public function handle(): int { $worker = $this->option('worker') ?: gethostname().':'.getmypid(); $connection = $this->option('connection') ?: config('queue.default');
$hb = WorkerHeartbeat::query()->updateOrCreate(['worker_name'=>$worker],['queue_name'=>$this->option('queue'),'queue_connection'=>$connection,'hostname'=>gethostname(),'process_id'=>(string)getmypid(),'last_seen_at'=>now()]);
$this->info("Heartbeat {$hb->worker_name} at {$hb->last_seen_at}"); return self::SUCCESS; }}
