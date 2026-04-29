<?php

namespace App\Services\Pipelines;

use App\Models\AiProvider;
use App\Models\AiRequestLog;
use App\Models\BulletinPromptRun;
use App\Models\User;
use App\Services\Ai\AiClientManager;
use App\Services\Ai\AiCostCalculator;
use App\Services\Ai\AiUsageLimitService;
use App\Services\Ai\Exceptions\AiProviderException;
use App\Services\EditorialReview\SourceReferenceExtractor;
use App\Services\PromptGeneration\BulletinPromptRunService;
use App\Services\Scripts\ScriptProductionMetadataGenerator;
use Throwable;

class BulletinPromptRunPipeline
{
    public function __construct(
        private readonly BulletinPromptRunService $runService,
        private readonly AiClientManager $aiClientManager,
        private readonly AiUsageLimitService $usageLimitService,
        private readonly AiCostCalculator $costCalculator,
        private readonly ScriptProductionMetadataGenerator $metadataGenerator,
        private readonly SourceReferenceExtractor $sourceExtractor,
    ) {}

    public function run(BulletinPromptRun $run, User $user = null, array $options = []): array
    {
        $options = array_merge([
            'force_regenerate_prompt' => false,
            'force_regenerate_ai_response' => false,
            'force_recreate_script' => false,
            'generate_metadata' => true,
            'extract_sources' => true,
            'dry_run' => false,
            'model' => null,
            'ai_provider_id' => null,
        ], $options);

        $summary = ['success'=>false,'steps'=>['prompt_generated'=>false,'ai_response_generated'=>false,'response_parsed'=>false,'script_created'=>false,'metadata_generated'=>false,'sources_extracted'=>false],'script_id'=>$run->script_id,'ai_request_log_id'=>null,'errors'=>[],'warnings'=>[]];

        if ($run->status === 'archived') { $summary['errors'][]='Archived prompt runs cannot be processed.'; return $summary; }
        if ($options['dry_run']) { $summary['warnings'][] = 'Dry run: no writes or AI calls executed.'; $summary['success']=true; return $summary; }

        $run->update(['pipeline_status'=>'running','pipeline_started_at'=>now(),'pipeline_finished_at'=>null,'pipeline_failed_at'=>null,'pipeline_error_message'=>null]);
        try {
            if (blank($run->generated_prompt) || $options['force_regenerate_prompt']) { $this->runService->generatePrompt($run); $summary['steps']['prompt_generated']=true; $run->update(['pipeline_status'=>'prompt_generated']); }
            if (filled($run->generated_prompt)) { $summary['steps']['prompt_generated']=true; }

            if (blank($run->ai_response_text) || $options['force_regenerate_ai_response']) {
                $provider = $this->resolveProvider($options['ai_provider_id'] ?? null);
                if (! $provider || ! $provider->is_active || ($provider->requiresApiKey() && ! $provider->hasConfiguredApiKey())) { throw new \RuntimeException('No valid AI provider configured.'); }
                $limitCheck = $this->usageLimitService->checkProviderLimits($provider);
                if ($limitCheck['blocked'] === true) { throw new \RuntimeException($limitCheck['warnings'][0] ?? 'AI provider limit reached.'); }
                $log = AiRequestLog::query()->create(['ai_provider_id'=>$provider->id,'bulletin_prompt_run_id'=>$run->id,'user_id'=>$user?->id,'model'=>$options['model'] ?? $provider->default_model,'status'=>'pending','request_type'=>'bulletin_prompt_run','prompt_hash'=>hash('sha256',(string)$run->generated_prompt),'prompt_preview'=>str((string)$run->generated_prompt)->limit(5000)->toString(),'started_at'=>now()]);
                $summary['ai_request_log_id']=$log->id;
                try {
                    $aiResponse = $this->aiClientManager->generateText($provider, (string) $run->generated_prompt, ['model' => $options['model'] ?? null]);
                    $this->runService->saveResponse($run, $aiResponse->text);
                    $log->update(['status'=>'success','response_preview'=>str($aiResponse->text)->limit(5000)->toString(),'model'=>$aiResponse->model,'input_tokens'=>$aiResponse->inputTokens,'output_tokens'=>$aiResponse->outputTokens,'total_tokens'=>$aiResponse->totalTokens,'duration_ms'=>$aiResponse->durationMs,'estimated_cost'=>$this->costCalculator->estimate($provider,$aiResponse->inputTokens,$aiResponse->outputTokens),'metadata'=>['finish_reason'=>$aiResponse->finishReason],'completed_at'=>now()]);
                } catch (AiProviderException|Throwable $e) { $log->update(['status'=>'failed','error_message'=>str($e->getMessage())->limit(1000)->toString(),'error_code'=>'provider_error','completed_at'=>now()]); throw $e; }
                $summary['steps']['ai_response_generated']=true; $summary['steps']['response_parsed']=true; $run->update(['pipeline_status'=>'ai_response_generated']);
            }
            if (filled($run->ai_response_text)) { $summary['steps']['ai_response_generated']=true; $summary['steps']['response_parsed']=true; }

            if (! $run->script_id || $options['force_recreate_script']) {
                if ($run->script_id && $options['force_recreate_script']) { $run->update(['script_id'=>null]); }
                $script=$this->runService->createScript($run->refresh()); $summary['script_id']=$script->id; $summary['steps']['script_created']=true; $run->update(['pipeline_status'=>'script_created']);
            } else { $summary['steps']['script_created']=true; }

            if ($options['generate_metadata'] && $run->script) { $run->script->update($this->metadataGenerator->generateForScript($run->script,$run)); $summary['steps']['metadata_generated']=true; }
            if ($options['extract_sources']) { $this->sourceExtractor->extractFromBulletinPromptRun($run->refresh()); $summary['steps']['sources_extracted']=true; }

            $summary['success']=true;
            $run->update(['pipeline_status'=>'completed','pipeline_finished_at'=>now(),'pipeline_metadata'=>$summary]);
        } catch (Throwable $e) {
            $summary['errors'][]=str($e->getMessage())->limit(500)->toString();
            $run->update(['pipeline_status'=>'failed','pipeline_failed_at'=>now(),'pipeline_error_message'=>$summary['errors'][0],'pipeline_metadata'=>$summary]);
        }
        return $summary;
    }

    private function resolveProvider(?int $providerId): ?AiProvider { return $providerId ? AiProvider::query()->find($providerId) : (AiProvider::query()->where('is_active', true)->where('is_default', true)->first() ?? AiProvider::query()->where('is_active',true)->orderByDesc('is_default')->first()); }
}
