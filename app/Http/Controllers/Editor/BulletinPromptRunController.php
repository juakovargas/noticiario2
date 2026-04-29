<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\AiProvider;
use App\Models\AiRequestLog;
use App\Models\BulletinPromptRun;
use App\Models\BulletinType;
use App\Models\PromptProfile;
use App\Models\User;
use App\Services\Ai\AiClientManager;
use App\Services\Ai\AiCostCalculator;
use App\Services\Ai\AiUsageLimitService;
use App\Services\Ai\Exceptions\AiProviderException;
use App\Services\PromptGeneration\BulletinCoverageWindowResolver;
use App\Services\PromptGeneration\BulletinPromptRunService;
use App\Services\BackgroundTasks\BackgroundTaskService;
use App\Jobs\GenerateBulletinPromptJob;
use App\Jobs\GenerateBulletinPromptRunAiResponseJob;
use App\Jobs\ExtractSourceReferencesJob;
use App\Services\Pipelines\BulletinPromptRunPipeline;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class BulletinPromptRunController extends Controller
{
    public function __construct(
        private readonly BulletinPromptRunService $service,
        private readonly BulletinCoverageWindowResolver $coverageWindowResolver,
        private readonly AiClientManager $aiClientManager,
        private readonly AiCostCalculator $costCalculator,
        private readonly AiUsageLimitService $usageLimitService,
        private readonly BackgroundTaskService $backgroundTaskService,
        private readonly BulletinPromptRunPipeline $pipelineService,
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = [
            'search' => (string) $request->query('search', ''),
            'status' => (string) $request->query('status', ''),
            'bulletin_type_id' => (string) $request->query('bulletin_type_id', ''),
            'prompt_profile_id' => (string) $request->query('prompt_profile_id', ''),
            'location_id' => (string) $request->query('location_id', ''),
            'created_by' => (string) $request->query('created_by', ''),
            'scheduled_from' => (string) $request->query('scheduled_from', ''),
            'scheduled_to' => (string) $request->query('scheduled_to', ''),
            'show_archived' => $request->boolean('show_archived'),
            'only_archived' => $request->boolean('only_archived'),
            'sort' => (string) $request->query('sort', 'updated_at'),
            'direction' => (string) $request->query('direction', 'desc'),
        ];

        $allowedSorts = [
            'updated_at',
            'scheduled_for',
            'created_at',
            'title',
            'status',
        ];

        $sort = in_array($filters['sort'], $allowedSorts, true)
            ? $filters['sort']
            : 'updated_at';

        $direction = in_array($filters['direction'], ['asc', 'desc'], true)
            ? $filters['direction']
            : 'desc';

        $query = BulletinPromptRun::query()
            ->with([
                'bulletinType:id,name',
                'promptProfile:id,name',
                'script:id,title,status,review_status,production_status,final_title,production_name,public_description,short_description,hashtags,target_platforms',
                'createdBy:id,name,email,profile_image_id',
            ])
            ->when(
                ! $filters['show_archived'] && ! $filters['only_archived'],
                fn (Builder $q) => $q->where('status', '!=', 'archived')
            )
            ->when(
                $filters['only_archived'],
                fn (Builder $q) => $q->where('status', 'archived')
            )
            ->when($filters['search'] !== '', function (Builder $q) use ($filters): void {
                $search = $filters['search'];

                $q->where(function (Builder $sq) use ($search): void {
                    $sq->where('title', 'like', "%{$search}%")
                        ->orWhere('generated_prompt', 'like', "%{$search}%")
                        ->orWhere('ai_response_text', 'like', "%{$search}%")
                        ->orWhereHas('bulletinType', fn (Builder $bt) => $bt->where('name', 'like', "%{$search}%"));
                });
            })
            ->when(
                $filters['status'] !== '',
                fn (Builder $q) => $q->where('status', $filters['status'])
            )
            ->when(
                $filters['bulletin_type_id'] !== '',
                fn (Builder $q) => $q->where('bulletin_type_id', $filters['bulletin_type_id'])
            )
            ->when(
                $filters['prompt_profile_id'] !== '',
                fn (Builder $q) => $q->where('prompt_profile_id', $filters['prompt_profile_id'])
            )
            ->when(
                $filters['location_id'] !== '',
                fn (Builder $q) => $q->whereHas('bulletinType', fn (Builder $bt) => $bt->where('location_id', $filters['location_id']))
            )
            ->when(
                $filters['created_by'] !== '',
                fn (Builder $q) => $q->where('created_by', $filters['created_by'])
            )
            ->when(
                $filters['scheduled_from'] !== '',
                fn (Builder $q) => $q->whereDate('scheduled_for', '>=', $filters['scheduled_from'])
            )
            ->when(
                $filters['scheduled_to'] !== '',
                fn (Builder $q) => $q->whereDate('scheduled_for', '<=', $filters['scheduled_to'])
            );

        if ($sort === 'scheduled_for') {
            $query->orderByRaw('scheduled_for is null asc');
        }

        $query->orderBy($sort, $direction);

        if ($sort !== 'updated_at') {
            $query->orderByDesc('updated_at');
        }

        return Inertia::render('Editor/BulletinPromptRuns/Index', [
            'runs' => $query->paginate(20)->withQueryString(),
            'filters' => $filters,
            'statuses' => [
                'draft',
                'prompt_ready',
                'waiting_ai_response',
                'response_received',
                'script_created',
                'completed',
                'cancelled',
                'failed',
                'archived',
            ],
            'bulletinTypes' => BulletinType::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'promptProfiles' => PromptProfile::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'users' => User::query()
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function show(BulletinPromptRun $bulletinPromptRun): Response
    {
        $bulletinPromptRun->load([
            'bulletinType.location:id,name',
            'bulletinType.newsCategory:id,name',
            'bulletinType.language:id,name,code',
            'promptProfile:id,name',
            'edition:id,title',
            'script:id,title,status,review_status,production_status,final_title,production_name,public_description,short_description,hashtags,target_platforms',
            'createdBy:id,name,email,profile_image_id',
            'sourceReferences.checkedBy:id,name,email,profile_image_id',
            'aiRequestLogs.provider:id,name',
        ]);

        $activeProvider = AiProvider::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->first();

        $sourceCounts = $bulletinPromptRun->sourceReferences
            ->whereNull('archived_at')
            ->groupBy('verification_status')
            ->map->count();

        $script = $bulletinPromptRun->script;
        if ($script) {
            $script->setAttribute('metadata_ready', (filled($script->final_title) || filled($script->production_name))
                && (filled($script->public_description) || filled($script->short_description))
                && is_array($script->hashtags) && count($script->hashtags) > 0
                && is_array($script->target_platforms) && count($script->target_platforms) > 0);
        }

        return Inertia::render('Editor/BulletinPromptRuns/Show', [
            'run' => $bulletinPromptRun,
            'promptContext' => $this->coverageWindowResolver->resolve($bulletinPromptRun),
            'aiProviders' => AiProvider::query()
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get()
                ->map(fn (AiProvider $provider) => [
                    'id' => $provider->id,
                    'name' => $provider->name,
                    'provider_type' => $provider->provider_type,
                    'default_model' => $provider->default_model,
                    'is_default' => $provider->is_default,
                    'is_active' => $provider->is_active,
                    'api_key_env_name' => $provider->api_key_env_name,
                    'env_key_configured' => $provider->hasConfiguredApiKey(),
                ]),
            'defaultAiProviderId' => $activeProvider?->id,
            'latestAiLog' => $bulletinPromptRun->aiRequestLogs->sortByDesc('id')->first(),
            'sourceReferences' => $bulletinPromptRun->sourceReferences
                ->whereNull('archived_at')
                ->map(fn ($reference) => [
                    'id' => $reference->id,
                    'title' => $reference->title,
                    'source_name' => $reference->source_name,
                    'source_url' => $reference->source_url,
                    'verification_status' => $reference->verification_status,
                    'checked_at' => $reference->checked_at?->toDateTimeString(),
                    'checked_by' => $reference->checkedBy ? [
                        'id' => $reference->checkedBy->id,
                        'name' => $reference->checkedBy->name,
                        'email' => $reference->checkedBy->email,
                        'avatar_url' => $reference->checkedBy->avatar_url,
                        'initials' => $reference->checkedBy->initials,
                    ] : null,
                ])->values(),
            'sourceSummary' => [
                'total' => $sourceCounts->sum(),
                'pending' => $sourceCounts->get('pending', 0),
                'verified' => $sourceCounts->get('verified', 0),
                'weak' => $sourceCounts->get('weak', 0),
                'missing' => $sourceCounts->get('missing', 0),
                'broken' => $sourceCounts->get('broken', 0),
                'rejected' => $sourceCounts->get('rejected', 0),
                'unresolved' => (int) ($sourceCounts->get('pending', 0) + $sourceCounts->get('weak', 0) + $sourceCounts->get('missing', 0) + $sourceCounts->get('broken', 0) + $sourceCounts->get('rejected', 0)),
            ],
        ]);
    }

    public function store(BulletinType $bulletinType, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'scheduled_for' => ['nullable', 'date'],
        ]);

        $run = $this->service->createFromBulletinType($bulletinType, $request->user()?->id, $data['scheduled_for'] ?? null);

        return to_route('editor.bulletin-prompt-runs.show', $run)->with('success', 'Prompt run created successfully.');
    }

    public function updateSchedule(Request $request, BulletinPromptRun $bulletinPromptRun): RedirectResponse
    {
        $data = $request->validate([
            'scheduled_for' => ['required', 'date'],
        ]);

        $bulletinPromptRun->update([
            'scheduled_for' => $data['scheduled_for'],
        ]);

        return back()->with('success', 'Scheduled date/time updated successfully.');
    }

    public function generatePrompt(BulletinPromptRun $bulletinPromptRun): RedirectResponse
    {
        $this->service->generatePrompt($bulletinPromptRun);

        return back()->with('success', 'Prompt generated successfully.');
    }

    public function generateAiResponse(Request $request, BulletinPromptRun $bulletinPromptRun): RedirectResponse
    {
        $data = $request->validate([
            'ai_provider_id' => ['nullable', 'exists:ai_providers,id'],
            'model' => ['nullable', 'string', 'max:255'],
        ]);

        if ($bulletinPromptRun->status === 'archived') {
            return back()->with('error', 'Archived prompt runs cannot generate AI responses.');
        }

        if (blank($bulletinPromptRun->generated_prompt)) {
            return back()->with('error', 'Generate prompt before requesting AI response.');
        }

        $provider = null;
        if (filled($data['ai_provider_id'] ?? null)) {
            $provider = AiProvider::query()->find($data['ai_provider_id']);
        }

        $provider ??= AiProvider::query()->where('is_active', true)->where('is_default', true)->first();
        $provider ??= AiProvider::query()->where('is_active', true)->orderByDesc('is_default')->first();

        if (! $provider || ! $provider->is_active) {
            return back()->with('error', 'No active AI provider configured.');
        }

        if ($provider->requiresApiKey() && ! $provider->hasConfiguredApiKey()) {
            return back()->with('error', 'Environment key is not configured.');
        }

        $limitCheck = $this->usageLimitService->checkProviderLimits($provider);
        if ($limitCheck['blocked'] === true) {
            AiRequestLog::query()->create([
                'ai_provider_id' => $provider->id,
                'bulletin_prompt_run_id' => $bulletinPromptRun->id,
                'user_id' => $request->user()?->id,
                'model' => $data['model'] ?? $provider->default_model,
                'status' => 'failed',
                'limit_blocked' => true,
                'request_type' => 'bulletin_prompt_run',
                'prompt_hash' => hash('sha256', (string) $bulletinPromptRun->generated_prompt),
                'prompt_preview' => str((string) $bulletinPromptRun->generated_prompt)->limit(5000)->toString(),
                'error_code' => 'limit_reached',
                'error_message' => $limitCheck['warnings'][0] ?? 'AI provider daily limit reached.',
                'started_at' => now(),
                'completed_at' => now(),
            ]);

            return back()->with('error', $limitCheck['warnings'][0] ?? 'AI provider daily limit reached.');
        }

        $log = AiRequestLog::query()->create([
            'ai_provider_id' => $provider->id,
            'bulletin_prompt_run_id' => $bulletinPromptRun->id,
            'user_id' => $request->user()?->id,
            'model' => $data['model'] ?? $provider->default_model,
            'status' => 'pending',
            'request_type' => 'bulletin_prompt_run',
            'prompt_hash' => hash('sha256', (string) $bulletinPromptRun->generated_prompt),
            'prompt_preview' => str((string) $bulletinPromptRun->generated_prompt)->limit(5000)->toString(),
            'started_at' => now(),
        ]);

        try {
            $aiResponse = $this->aiClientManager->generateText($provider, (string) $bulletinPromptRun->generated_prompt, [
                'model' => $data['model'] ?? null,
            ]);

            $this->service->saveResponse($bulletinPromptRun, $aiResponse->text);

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

            return back()->with('success', 'Response generated successfully.');
        } catch (AiProviderException|Throwable $exception) {
            $log->update([
                'status' => 'failed',
                'error_message' => str($exception->getMessage())->limit(1000)->toString(),
                'error_code' => 'provider_error',
                'provider_status_code' => null,
                'completed_at' => now(),
            ]);

            return back()->with('error', 'AI request failed.');
        }
    }


    public function generatePromptQueued(Request $request, BulletinPromptRun $bulletinPromptRun): RedirectResponse
    {
        $task = $this->backgroundTaskService->create('generate_bulletin_prompt', $bulletinPromptRun, $request->user());
        GenerateBulletinPromptJob::dispatch($bulletinPromptRun->id, $task->id);

        return back()->with('success', 'Task queued successfully');
    }

    public function generateAiResponseQueued(Request $request, BulletinPromptRun $bulletinPromptRun): RedirectResponse
    {
        $data = $request->validate(['ai_provider_id' => ['nullable', 'exists:ai_providers,id'], 'model' => ['nullable', 'string', 'max:255']]);
        $task = $this->backgroundTaskService->create('generate_ai_response', $bulletinPromptRun, $request->user());
        GenerateBulletinPromptRunAiResponseJob::dispatch($bulletinPromptRun->id, $data['ai_provider_id'] ?? null, $request->user()?->id, $task->id, $data['model'] ?? null);

        return back()->with('success', 'Task queued successfully');
    }

    public function extractSourcesQueued(Request $request, BulletinPromptRun $bulletinPromptRun): RedirectResponse
    {
        $task = $this->backgroundTaskService->create('extract_sources', $bulletinPromptRun, $request->user());
        ExtractSourceReferencesJob::dispatch('bulletin_prompt_run', $bulletinPromptRun->id, $request->user()?->id, $task->id);

        return back()->with('success', 'Task queued successfully');
    }
    public function saveResponse(Request $request, BulletinPromptRun $bulletinPromptRun): RedirectResponse
    {
        $data = $request->validate([
            'response_text' => ['required', 'string'],
        ]);

        $this->service->saveResponse($bulletinPromptRun, $data['response_text']);

        $message = $bulletinPromptRun->script_id
            ? 'Response processed successfully. Existing script will not be overwritten.'
            : 'Response processed successfully.';

        return back()->with('success', $message);
    }

    public function createScript(BulletinPromptRun $bulletinPromptRun): RedirectResponse
    {
        if ($bulletinPromptRun->script_id) {
            return to_route('editor.scripts.show', $bulletinPromptRun->script_id)
                ->with('success', 'Open existing script. Existing script will not be overwritten.');
        }

        $script = $this->service->createScript($bulletinPromptRun);

        return to_route('editor.scripts.show', $script)->with('success', 'Script created from prompt run.');
    }


    public function runPipeline(Request $request, BulletinPromptRun $bulletinPromptRun): RedirectResponse
    {
        $data = $request->validate([
            'ai_provider_id' => ['nullable', 'exists:ai_providers,id'],
            'model' => ['nullable', 'string', 'max:255'],
            'force_regenerate_prompt' => ['nullable', 'boolean'],
            'force_regenerate_ai_response' => ['nullable', 'boolean'],
            'generate_metadata' => ['nullable', 'boolean'],
            'extract_sources' => ['nullable', 'boolean'],
            'force_recreate_script' => ['nullable', 'boolean'],
        ]);

        $summary = $this->pipelineService->run($bulletinPromptRun, $request->user(), $data);

        if (! $summary['success']) {
            return back()->with('error', $summary['errors'][0] ?? 'Pipeline failed and needs manual review.');
        }

        return back()->with('success', 'Pipeline completed successfully.');
    }

    public function retryPipeline(Request $request, BulletinPromptRun $bulletinPromptRun): RedirectResponse
    {
        $summary = $this->pipelineService->run($bulletinPromptRun, $request->user(), [
            'generate_metadata' => true,
            'extract_sources' => true,
        ]);

        if (! $summary['success']) {
            return back()->with('error', $summary['errors'][0] ?? 'Pipeline failed and needs manual review.');
        }

        return back()->with('success', 'Pipeline completed successfully.');
    }
    public function archive(BulletinPromptRun $bulletinPromptRun): RedirectResponse
    {
        if ($bulletinPromptRun->status !== 'archived') {
            $metadata = is_array($bulletinPromptRun->metadata) ? $bulletinPromptRun->metadata : [];
            $metadata['previous_status'] = $bulletinPromptRun->status;

            $bulletinPromptRun->update([
                'status' => 'archived',
                'metadata' => $metadata,
            ]);
        }

        return back()->with('success', 'Prompt run archived successfully.');
    }

    public function restore(BulletinPromptRun $bulletinPromptRun): RedirectResponse
    {
        $metadata = is_array($bulletinPromptRun->metadata) ? $bulletinPromptRun->metadata : [];
        $previousStatus = $metadata['previous_status'] ?? null;
        $safeStatuses = ['draft', 'prompt_ready', 'waiting_ai_response', 'response_received', 'script_created', 'completed', 'cancelled', 'failed'];
        if (in_array($previousStatus, $safeStatuses, true)) {
            $status = $previousStatus;
        } elseif ($bulletinPromptRun->script_id) {
            $status = 'script_created';
        } elseif (filled($bulletinPromptRun->ai_response_text)) {
            $status = 'response_received';
        } elseif (filled($bulletinPromptRun->generated_prompt)) {
            $status = 'prompt_ready';
        } else {
            $status = 'draft';
        }

        unset($metadata['previous_status']);

        $bulletinPromptRun->update([
            'status' => $status,
            'metadata' => $metadata,
        ]);

        return back()->with('success', 'Prompt run restored successfully.');
    }

    public function markCompleted(BulletinPromptRun $bulletinPromptRun): RedirectResponse
    {
        $bulletinPromptRun->update(['status' => 'completed']);

        return back()->with('success', 'Prompt run marked as completed.');
    }

    public function cancel(BulletinPromptRun $bulletinPromptRun): RedirectResponse
    {
        if ($bulletinPromptRun->script_id) {
            return back()->with('error', 'Prompt run cannot be cancelled after script creation.');
        }

        $bulletinPromptRun->update(['status' => 'cancelled']);

        return back()->with('success', 'Prompt run cancelled.');
    }
}
