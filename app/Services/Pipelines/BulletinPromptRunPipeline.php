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

    public function run(BulletinPromptRun $run, ?User $user = null, array $options = []): array
    {
        $options = array_merge([
            'ai_provider_id' => null,
            'model' => null,
            'force_regenerate_prompt' => false,
            'force_regenerate_ai_response' => false,
            'force_recreate_script' => false,
            'generate_metadata' => true,
            'extract_sources' => true,
            'allow_ai_call' => false,
            'dry_run' => false,
        ], $options);

        $summary = [
            'success' => false,
            'failed_step' => null,
            'message' => 'Pipeline failed.',
            'script_id' => $run->script_id,
            'bulletin_prompt_run_id' => $run->id,
            'editorial_schedule_run_id' => $run->editorial_schedule_run_id,
            'ai_request_log_id' => null,
            'steps' => [
                'prompt_generated' => false,
                'ai_response_generated' => false,
                'response_parsed' => false,
                'script_created' => false,
                'metadata_generated' => false,
                'sources_extracted' => false,
            ],
            'errors' => [],
            'warnings' => [],
        ];

        if ($run->status === 'archived') {
            return $this->fail($run, $summary, 'validation', 'Archived prompt runs cannot be processed.');
        }

        if ($options['dry_run']) {
            $summary['success'] = true;
            $summary['message'] = 'Dry run completed.';
            $summary['warnings'][] = 'Dry run: no writes or AI calls executed.';
            return $summary;
        }

        $run->update(['pipeline_status' => 'running', 'pipeline_started_at' => now(), 'pipeline_finished_at' => null, 'pipeline_failed_at' => null, 'pipeline_failed_step' => null, 'pipeline_error_message' => null]);

        try {
            if (blank($run->generated_prompt) || $options['force_regenerate_prompt']) {
                $this->runService->generatePrompt($run);
            }
            $summary['steps']['prompt_generated'] = filled($run->fresh()->generated_prompt);
            if (! $summary['steps']['prompt_generated']) {
                return $this->fail($run, $summary, 'prompt_generation', 'Prompt generation failed.');
            }

            if (blank($run->fresh()->ai_response_text) || $options['force_regenerate_ai_response']) {
                if (! $options['allow_ai_call']) {
                    $summary['warnings'][] = 'Waiting for manual action.';
                } else {
                    $provider = $this->resolveProvider($options['ai_provider_id']);
                    if (! $provider || ! $provider->is_active) {
                        return $this->fail($run, $summary, 'ai_provider', 'Provider missing.');
                    }
                    if ($provider->requiresApiKey() && ! $provider->hasConfiguredApiKey()) {
                        return $this->fail($run, $summary, 'ai_provider', 'API key missing.');
                    }
                    $limitCheck = $this->usageLimitService->checkProviderLimits($provider);
                    if ($limitCheck['blocked'] === true) {
                        return $this->fail($run, $summary, 'ai_response_generation', $limitCheck['warnings'][0] ?? 'Daily request limit reached.');
                    }

                    $log = AiRequestLog::query()->create([
                        'ai_provider_id' => $provider->id,
                        'bulletin_prompt_run_id' => $run->id,
                        'user_id' => $user?->id,
                        'model' => $options['model'] ?? $provider->default_model,
                        'status' => 'pending',
                        'request_type' => 'bulletin_prompt_run',
                        'prompt_hash' => hash('sha256', (string) $run->generated_prompt),
                        'prompt_preview' => str((string) $run->generated_prompt)->limit(5000)->toString(),
                        'started_at' => now(),
                    ]);

                    $summary['ai_request_log_id'] = $log->id;

                    try {
                        $aiResponse = $this->aiClientManager->generateText($provider, (string) $run->generated_prompt, ['model' => $options['model'] ?? null]);
                        $this->runService->saveResponse($run, $aiResponse->text);
                        $log->update([
                            'status' => 'success',
                            'response_preview' => str($aiResponse->text)->limit(5000)->toString(),
                            'model' => $aiResponse->model,
                            'input_tokens' => $aiResponse->inputTokens,
                            'output_tokens' => $aiResponse->outputTokens,
                            'total_tokens' => $aiResponse->totalTokens,
                            'duration_ms' => $aiResponse->durationMs,
                            'estimated_cost' => $this->costCalculator->estimate($provider, $aiResponse->inputTokens, $aiResponse->outputTokens),
                            'metadata' => ['finish_reason' => $aiResponse->finishReason],
                            'completed_at' => now(),
                        ]);
                    } catch (AiProviderException|Throwable $exception) {
                        $log->update(['status' => 'failed', 'error_message' => str($exception->getMessage())->limit(1000)->toString(), 'error_code' => 'provider_error', 'completed_at' => now()]);
                        return $this->fail($run, $summary, 'ai_response_generation', 'AI request failed.');
                    }
                }
            }

            $refreshed = $run->fresh();
            $summary['steps']['ai_response_generated'] = filled($refreshed->ai_response_text);
            $summary['steps']['response_parsed'] = is_array($refreshed->parsed_response) && $refreshed->parsed_response !== [];

            if (! $summary['steps']['ai_response_generated'] && $options['allow_ai_call']) {
                return $this->fail($run, $summary, 'ai_response_generation', 'AI response generation failed.');
            }

            if (! $refreshed->script_id || $options['force_recreate_script']) {
                if ($refreshed->script_id && $options['force_recreate_script']) {
                    $refreshed->update(['script_id' => null]);
                }
                if (blank($refreshed->ai_response_text)) {
                    return $this->fail($run, $summary, 'script_creation', 'Script creation requires an AI response.');
                }
                $script = $this->runService->createScript($refreshed->fresh());
                $summary['script_id'] = $script->id;
                $summary['steps']['script_created'] = true;
            } else {
                $summary['script_id'] = $refreshed->script_id;
                $summary['steps']['script_created'] = true;
                $summary['warnings'][] = 'Existing script reused.';
            }

            $run = $run->fresh();
            if ($options['generate_metadata'] && $run->script) {
                $run->script->update($this->metadataGenerator->generateForScript($run->script, $run));
                $summary['steps']['metadata_generated'] = true;
            }
            if ($options['extract_sources']) {
                $this->sourceExtractor->extractFromBulletinPromptRun($run);
                $summary['steps']['sources_extracted'] = true;
            }

            $summary['success'] = true;
            $summary['message'] = 'Pipeline completed. Script created.';
            $run->update(['pipeline_status' => 'completed', 'pipeline_finished_at' => now(), 'pipeline_metadata' => $summary]);
            return $summary;
        } catch (Throwable $exception) {
            return $this->fail($run, $summary, 'response_parsing', str($exception->getMessage())->limit(500)->toString());
        }
    }

    private function fail(BulletinPromptRun $run, array $summary, string $failedStep, string $message): array
    {
        $summary['failed_step'] = $failedStep;
        $summary['message'] = $message;
        $summary['errors'][] = $message;
        $run->update(['pipeline_status' => 'failed', 'pipeline_failed_at' => now(), 'pipeline_failed_step' => $failedStep, 'pipeline_error_message' => $message, 'pipeline_metadata' => $summary]);
        return $summary;
    }

    private function resolveProvider(?int $providerId): ?AiProvider
    {
        return $providerId
            ? AiProvider::query()->find($providerId)
            : (AiProvider::query()->where('is_active', true)->where('slug', 'groq')->first()
                ?? AiProvider::query()->where('is_active', true)->where('is_default', true)->first()
                ?? AiProvider::query()->where('is_active', true)->orderByRaw("CASE WHEN slug = 'groq' THEN 0 ELSE 1 END")->orderByDesc('is_default')->first());
    }
}
