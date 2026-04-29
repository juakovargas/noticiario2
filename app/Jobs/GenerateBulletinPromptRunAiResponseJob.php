<?php
namespace App\Jobs;
use App\Jobs\Concerns\TracksBackgroundTask;
use App\Models\AiProvider; use App\Models\AiRequestLog; use App\Models\BulletinPromptRun;
use App\Services\Ai\AiClientManager; use App\Services\Ai\AiCostCalculator; use App\Services\Ai\AiUsageLimitService;
use App\Services\PromptGeneration\BulletinPromptRunService;
use Illuminate\Bus\Queueable; use Illuminate\Contracts\Queue\ShouldQueue; use Illuminate\Foundation\Bus\Dispatchable; use Illuminate\Queue\InteractsWithQueue; use Illuminate\Queue\SerializesModels;
class GenerateBulletinPromptRunAiResponseJob implements ShouldQueue { use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TracksBackgroundTask;
public function __construct(public int $bulletinPromptRunId, public ?int $aiProviderId = null, public ?int $userId = null, ?int $backgroundTaskId = null, public ?string $model = null){$this->backgroundTaskId=$backgroundTaskId;}
public function handle(AiClientManager $manager,AiCostCalculator $cost,AiUsageLimitService $limits,BulletinPromptRunService $service): void {
$this->markTaskRunning('Generating AI response'); $run=BulletinPromptRun::query()->findOrFail($this->bulletinPromptRunId); if($run->status==='archived'||blank($run->generated_prompt)) throw new \RuntimeException('Invalid prompt run state.');
$provider=$this->aiProviderId?AiProvider::find($this->aiProviderId):AiProvider::query()->where('is_active',true)->orderByDesc('is_default')->first(); if(! $provider) throw new \RuntimeException('No active AI provider configured.');
$check=$limits->checkProviderLimits($provider); if(($check['blocked']??false)===true) throw new \RuntimeException($check['warnings'][0] ?? 'AI provider daily limit reached.');
$started=now(); $response=$manager->generateText($provider, (string)$run->generated_prompt, $this->model ?: $provider->default_model);
$service->saveResponse($run,(string)$response->text); AiRequestLog::query()->create(['ai_provider_id'=>$provider->id,'bulletin_prompt_run_id'=>$run->id,'user_id'=>$this->userId,'model'=>$this->model ?: $provider->default_model,'status'=>'success','request_type'=>'bulletin_prompt_run','prompt_hash'=>hash('sha256',(string)$run->generated_prompt),'prompt_preview'=>str((string)$run->generated_prompt)->limit(5000)->toString(),'response_preview'=>str((string)$response->text)->limit(5000)->toString(),'input_tokens'=>$response->inputTokens,'output_tokens'=>$response->outputTokens,'total_tokens'=>$response->totalTokens,'estimated_cost'=>$cost->estimate($provider,$response->inputTokens,$response->outputTokens),'started_at'=>$started,'completed_at'=>now()]);
$this->markTaskCompleted('AI generation queued'); }
public function failed(\Throwable $e): void { $this->markTaskFailed($e);} }
