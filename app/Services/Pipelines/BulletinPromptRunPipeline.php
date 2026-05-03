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
                    $provider = $this->resolveProvider($run, $options['ai_provider_id']);
                    if (! $provider || ! $provider->is_active) {
                        if ($this->requiresGroundedProvider($run)) {
                            return $this->fail($run, $summary, 'ai_provider', 'No grounded news provider is configured for this informativo.');
                        }
                        return $this->fail($run, $summary, 'ai_provider', 'No AI provider configured for this bulletin.');
                    }
                    $selectedProviderMeta = [
                        'selected_provider_id' => $provider->id,
                        'selected_provider_name' => $provider->name,
                        'selected_provider_type' => $provider->provider_type,
                        'selected_provider_capabilities' => $provider->capabilities ?? [],
                        'selected_provider_grounded' => (bool) $provider->supports_grounding,
                        'selected_model' => $options['model'] ?? $provider->default_model,
                    ];
                    $run->update(['metadata' => array_merge((array) ($run->metadata ?? []), $selectedProviderMeta)]);
                    $summary['pipeline_metadata'] = array_merge($summary['pipeline_metadata'] ?? [], $selectedProviderMeta);
                    if ($provider->requiresApiKey() && ! $provider->hasConfiguredApiKey()) {
                        return $this->fail($run, $summary, 'ai_provider', sprintf('Environment key %s is not configured.', $provider->apiKeyEnvName() ?: 'API_KEY'));
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
                        $aiResponse = $this->aiClientManager->generateText($provider, (string) $run->generated_prompt, [
                            'model' => $options['model'] ?? null,
                            'grounding_enabled' => $this->requiresGroundedProvider($run),
                        ]);
                        $this->runService->saveResponse($run, $aiResponse->text);
                        $run = $run->fresh();
                        $this->syncEditorialScheduleRun($run);
                        $metadata = (array) ($run->metadata ?? []);
                        $metadata['grounded'] = (bool) $provider->supports_grounding;
                        $metadata['ai_response_metadata'] = $aiResponse->metadata;
                        if ((bool) $provider->supports_grounding && blank(data_get($aiResponse->metadata, 'grounding'))) {
                            $summary['warnings'][] = 'Gemini response did not include extractable source URLs.';
                        }
                        $run->update(['metadata' => $metadata]);
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
                        $isRetryable = $exception instanceof AiProviderException && $exception->retryable;
                        $retryAfter = $exception instanceof AiProviderException ? $exception->retryAfterSeconds : null;
                        $isInternal = $exception instanceof AiProviderException ? ! $exception->requestWasSent : false;
                        $log->update([
                            'status' => $isRetryable ? 'rate_limited' : 'failed',
                            'error_message' => str($exception->getMessage())->limit(1000)->toString(),
                            'error_code' => $exception instanceof AiProviderException ? ($exception->errorCode ?? ($isRetryable ? 'provider_rate_limit' : 'provider_error')) : 'provider_error',
                            'provider_status_code' => $exception instanceof AiProviderException ? $exception->statusCode : null,
                            'metadata' => ['retryable' => $isRetryable, 'retry_after_seconds' => $retryAfter, 'source' => $isInternal ? 'internal' : 'provider', 'request_was_sent' => ! $isInternal, 'rate_limit_source' => $isInternal ? 'internal' : 'provider', 'grounding_enabled' => (bool) $provider->supports_grounding, 'attempt' => data_get($provider->rate_limit_metadata, 'attempt'), 'max_retries' => data_get($provider->rate_limit_metadata, 'max_retries'), 'rate_limited_until' => optional($provider->fresh()->rate_limited_until)?->toISOString(), 'min_seconds_between_requests' => $provider->min_seconds_between_requests, 'last_request_at' => optional($provider->fresh()->last_request_at)?->toISOString()],
                            'completed_at' => now()
                        ]);
                        if ($isRetryable) {
                            $rateLimitedUntil = now()->addSeconds(max(1, (int) $retryAfter))->toISOString();
                            $summary['pipeline_metadata']['retry_after_seconds'] = $retryAfter;
                            $summary['pipeline_metadata']['rate_limited_until'] = $rateLimitedUntil;
                            $summary['pipeline_metadata']['provider_id'] = $provider->id;
                            $summary['pipeline_metadata']['provider_name'] = $provider->name;
                            
                            $summary['pipeline_metadata']['rate_limit_source'] = $isInternal ? 'internal_rate_limiter' : 'provider_response';
                            $summary['pipeline_metadata']['provider_status_code'] = $exception instanceof AiProviderException ? $exception->statusCode : null;
                            $summary['pipeline_metadata']['attempt'] = data_get($provider->rate_limit_metadata, 'attempt');
                            $summary['pipeline_metadata']['max_retries'] = data_get($provider->rate_limit_metadata, 'max_retries');
                            return $this->fail($run, $summary, 'ai_response_generation', $exception->getMessage(), 'waiting_rate_limit');
                        }
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
                $this->syncEditorialScheduleRun($refreshed->fresh());
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
            $this->syncEditorialScheduleRun($run->fresh());
            return $summary;
        } catch (Throwable $exception) {
            return $this->fail($run, $summary, 'response_parsing', str($exception->getMessage())->limit(500)->toString());
        }
    }

    private function fail(BulletinPromptRun $run, array $summary, string $failedStep, string $message, string $status = 'failed_permanent'): array
    {
        $summary['failed_step'] = $failedStep;
        $summary['message'] = $message;
        $summary['errors'][] = $message;
        $run->update(['pipeline_status' => $status, 'pipeline_failed_at' => now(), 'pipeline_failed_step' => $failedStep, 'pipeline_error_message' => $message, 'pipeline_metadata' => $summary]);
        $this->syncEditorialScheduleRun($run->fresh(), $message);
        return $summary;
    }

    private function syncEditorialScheduleRun(BulletinPromptRun $run, ?string $errorMessage = null): void
    {
        if (! $run->editorial_schedule_run_id) {
            return;
        }

        $status = match (true) {
            $errorMessage !== null => 'failed',
            filled($run->script_id) => 'script_created',
            filled($run->ai_response_text) => 'response_received',
            filled($run->generated_prompt) => 'prompt_generated',
            default => 'prompt_run_created',
        };

        $run->editorialScheduleRun()->update([
            'generated_prompt' => $run->generated_prompt,
            'ai_response_text' => $run->ai_response_text,
            'parsed_response' => $run->parsed_response,
            'prompt_generated_at' => $run->prompt_generated_at,
            'response_received_at' => $run->response_received_at,
            'script_id' => $run->script_id,
            'script_created_at' => $run->script_created_at,
            'status' => $status,
            'error_message' => $errorMessage,
        ]);
    }

    private function resolveProvider(BulletinPromptRun $run, ?int $providerId): ?AiProvider
    {
        $requiresGrounded = $this->requiresGroundedProvider($run);
        if ($providerId) {
            $explicit = AiProvider::query()->find($providerId);
            if ($requiresGrounded && $explicit && ! $this->isGroundedProvider($explicit)) {
                return null;
            }
            return $explicit;
        }

        $preferredId = $run->bulletinType?->preferred_ai_provider_id ?? null;
        if ($preferredId) {
            $preferred = AiProvider::query()->active()->find($preferredId);
            if ($preferred && (! $requiresGrounded || $this->isGroundedProvider($preferred))) {
                return $preferred;
            }
        }

        return null;
    }

    private function requiresGroundedProvider(BulletinPromptRun $run): bool
    {
        $meta = (array) ($run->bulletinType?->metadata ?? []);
        return (bool) (data_get($meta, 'requires_current_news') || data_get($meta, 'requires_grounded_news') || data_get($meta, 'grounded_news_required'));
    }

    private function isGroundedProvider(AiProvider $provider): bool
    {
        return (bool) ($provider->supports_grounding
            || $provider->provider_category === 'grounded_text'
            || $provider->supportsCapability('news_grounding')
            || $provider->supportsCapability('google_search_grounding'));
    }
}
