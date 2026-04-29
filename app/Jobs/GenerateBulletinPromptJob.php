<?php
namespace App\Jobs;
use App\Jobs\Concerns\TracksBackgroundTask;
use App\Models\BulletinPromptRun;
use App\Services\PromptGeneration\BulletinPromptRunService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateBulletinPromptJob implements ShouldQueue { use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TracksBackgroundTask;
public function __construct(public int $bulletinPromptRunId, ?int $backgroundTaskId = null){$this->backgroundTaskId=$backgroundTaskId;}
public function handle(BulletinPromptRunService $service): void { $this->markTaskRunning('Generating prompt'); $run=BulletinPromptRun::query()->findOrFail($this->bulletinPromptRunId); $service->generatePrompt($run); $this->markTaskCompleted('Prompt generation queued', ['run_id'=>$run->id]); }
public function failed(\Throwable $e): void { $this->markTaskFailed($e); }}
