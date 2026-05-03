<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\EditorialSchedule;
use App\Models\EditorialScheduleRun;
use App\Services\EditorialScheduling\EditorialScheduleRunService;
use App\Services\PromptGeneration\BulletinPromptRunService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class EditorialScheduleRunController extends Controller
{
    public function __construct(private readonly EditorialScheduleRunService $service, private readonly BulletinPromptRunService $promptRunService)
    {
    }

    public function index(Request $request): Response
    {
        $filters = [
            'search' => (string) $request->query('search', ''),
            'status' => (string) $request->query('status', ''),
            'schedule_id' => (string) $request->query('schedule_id', ''),
            'scheduled_from' => (string) $request->query('scheduled_from', ''),
            'scheduled_to' => (string) $request->query('scheduled_to', ''),
            'show_archived' => $request->boolean('show_archived'),
            'only_archived' => $request->boolean('only_archived'),
        ];

        $query = EditorialScheduleRun::query()->with([
            'schedule:id,name,edition_type,frequency_type,run_frequency,run_time,scheduled_time,timezone,bulletin_type_id,auto_generate_ai_response,auto_run_pipeline',
            'schedule.bulletinType:id,name,location_id,news_category_id,language_id,preferred_ai_provider_id',
            'schedule.bulletinType.location:id,name',
            'schedule.bulletinType.newsCategory:id,name',
            'schedule.bulletinType.language:id,name,code',
            'schedule.bulletinType.preferredAiProvider:id,name,default_model,supports_grounding,is_active',
            'bulletinPromptRun:id,title,status,generated_prompt,ai_response_text,script_id,editorial_schedule_run_id',
            'edition:id,title,language,scheduled_for',
            'script:id,title,status',
            'sourceReferences:id,editorial_schedule_run_id,verification_status',
        ]);

        $query
            ->when($filters['search'] !== '', fn ($q) => $q->whereHas('schedule', fn ($sq) => $sq->where('name', 'like', '%'.$filters['search'].'%')))
            ->when($filters['status'] !== '', fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['schedule_id'] !== '', fn ($q) => $q->where('editorial_schedule_id', $filters['schedule_id']))
            ->when($filters['scheduled_from'] !== '', fn ($q) => $q->whereDate('scheduled_for', '>=', $filters['scheduled_from']))
            ->when($filters['scheduled_to'] !== '', fn ($q) => $q->whereDate('scheduled_for', '<=', $filters['scheduled_to']))
            ->when(! $filters['show_archived'] && ! $filters['only_archived'], fn ($q) => $q->where('status', '!=', 'archived'))
            ->when($filters['only_archived'], fn ($q) => $q->where('status', 'archived'));

        return Inertia::render('Editor/EditorialScheduleRuns/Index', [
            'runs' => $query->latest('scheduled_for')->paginate(20)->withQueryString()->through(fn (EditorialScheduleRun $run) => $this->presentRun($run)),
            'filters' => $filters,
            'statuses' => ['pending', 'prompt_ready', 'waiting_ai_response', 'response_received', 'script_created', 'completed', 'failed', 'cancelled', 'archived'],
            'schedules' => EditorialSchedule::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(EditorialScheduleRun $editorialScheduleRun): Response
    {
        $editorialScheduleRun->load([
            'schedule.location:id,name', 'schedule.newsCategory:id,name', 'schedule.language:id,name,code',
            'schedule.bulletinType.location:id,name',
            'schedule.bulletinType.newsCategory:id,name',
            'schedule.bulletinType.language:id,name,code',
            'schedule.bulletinType.preferredAiProvider:id,name,default_model,supports_grounding,is_active',
            'bulletinPromptRun:id,title,status,generated_prompt,ai_response_text,parsed_response,script_id,editorial_schedule_run_id',
            'edition:id,title,language,scheduled_for', 'script:id,title,status',
            'sourceReferences:id,editorial_schedule_run_id,verification_status',
        ]);

        return Inertia::render('Editor/EditorialScheduleRuns/Show', [
            'run' => $this->presentRun($editorialScheduleRun),
        ]);
    }

    public function generatePrompt(EditorialScheduleRun $editorialScheduleRun): RedirectResponse
    {
        $editorialScheduleRun->loadMissing('schedule.bulletinType', 'bulletinPromptRun');

        if (! $editorialScheduleRun->bulletinPromptRun && $editorialScheduleRun->schedule?->bulletinType) {
            $promptRun = $this->promptRunService->createFromBulletinType(
                $editorialScheduleRun->schedule->bulletinType,
                request()->user()?->id,
                $editorialScheduleRun->scheduled_for?->toIso8601String(),
            );

            $promptRun->forceFill([
                'editorial_schedule_id' => $editorialScheduleRun->editorial_schedule_id,
                'editorial_schedule_run_id' => $editorialScheduleRun->id,
            ])->save();

            $editorialScheduleRun->forceFill([
                'bulletin_prompt_run_id' => $promptRun->id,
                'status' => 'prompt_run_created',
            ])->save();
        }

        if ($editorialScheduleRun->fresh()->bulletinPromptRun) {
            $this->promptRunService->generatePrompt($editorialScheduleRun->fresh()->bulletinPromptRun);
        } else {
            $this->service->generatePrompt($editorialScheduleRun);
        }

        return back()->with('success', 'Prompt generated successfully.');
    }

    public function receiveResponse(Request $request, EditorialScheduleRun $editorialScheduleRun): RedirectResponse
    {
        $data = $request->validate([
            'response_text' => ['required', 'string'],
        ]);

        $this->service->receiveAiResponse($editorialScheduleRun, $data['response_text']);

        return back()->with('success', 'AI response saved successfully.');
    }

    public function createScript(EditorialScheduleRun $editorialScheduleRun): RedirectResponse
    {
        $script = $this->service->createScriptFromResponse($editorialScheduleRun);

        return to_route('editor.scripts.show', $script)->with('success', 'Script created from AI response.');
    }

    public function complete(EditorialScheduleRun $editorialScheduleRun): RedirectResponse
    {
        $editorialScheduleRun->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Run marked as completed.');
    }

    public function archive(EditorialScheduleRun $editorialScheduleRun): RedirectResponse
    {
        if ($editorialScheduleRun->status !== 'archived') {
            $metadata = is_array($editorialScheduleRun->metadata) ? $editorialScheduleRun->metadata : [];
            $metadata['previous_status'] = $editorialScheduleRun->status;
            $editorialScheduleRun->update(['status' => 'archived', 'metadata' => $metadata]);
        }

        return back()->with('success', 'Run archived successfully.');
    }

    public function restore(EditorialScheduleRun $editorialScheduleRun): RedirectResponse
    {
        $metadata = is_array($editorialScheduleRun->metadata) ? $editorialScheduleRun->metadata : [];
        $previousStatus = (string) ($metadata['previous_status'] ?? '');
        $restored = $previousStatus !== '' && $previousStatus !== 'archived' ? $previousStatus : 'pending';
        $editorialScheduleRun->update(['status' => $restored]);

        return back()->with('success', 'Run restored successfully.');
    }

    private function presentRun(EditorialScheduleRun $run): array
    {
        $promptRun = $run->bulletinPromptRun;
        $provider = $run->schedule?->bulletinType?->preferredAiProvider;
        $promptGenerated = filled($promptRun?->generated_prompt) || filled($run->generated_prompt);
        $aiResponseReceived = filled($promptRun?->ai_response_text) || filled($run->ai_response_text);
        $scriptCreated = filled($promptRun?->script_id) || filled($run->script_id);
        $sourceReferences = $run->relationLoaded('sourceReferences') ? $run->sourceReferences : collect();
        $sourcesTotal = $sourceReferences->count();
        $sourcesPending = $sourceReferences->where('verification_status', 'pending')->count();
        $sourcesVerified = $sourceReferences->where('verification_status', 'verified')->count();
        $sourcesRejected = $sourceReferences->whereIn('verification_status', ['rejected', 'broken', 'missing'])->count();

        return [
            'id' => $run->id,
            'editorial_schedule_id' => $run->editorial_schedule_id,
            'bulletin_prompt_run_id' => $run->bulletin_prompt_run_id,
            'script_id' => $run->script_id ?: $promptRun?->script_id,
            'edition_id' => $run->edition_id,
            'scheduled_for' => optional($run->scheduled_for)?->toIso8601String(),
            'status' => $run->status,
            'generated_prompt' => $promptRun?->generated_prompt ?: $run->generated_prompt,
            'ai_response_text' => $promptRun?->ai_response_text ?: $run->ai_response_text,
            'parsed_response' => $promptRun?->parsed_response ?: $run->parsed_response,
            'parser_warnings' => $run->parser_warnings,
            'error_message' => $run->error_message,
            'prompt_generated' => $promptGenerated,
            'ai_response_received' => $aiResponseReceived,
            'script_created' => $scriptCreated,
            'sources' => [
                'total' => $sourcesTotal,
                'pending' => $sourcesPending,
                'verified' => $sourcesVerified,
                'rejected' => $sourcesRejected,
                'status' => $sourcesTotal === 0
                    ? 'none'
                    : ($sourcesRejected > 0
                        ? 'rejected'
                        : ($sourcesPending > 0
                            ? ($sourcesVerified > 0 ? 'mixed' : 'pending')
                            : 'verified')),
                'url' => $this->safeRoute('editor.source-references.index'),
            ],
            'ai_manual_approval_required' => ! (bool) $run->schedule?->auto_generate_ai_response,
            'schedule' => $run->schedule ? [
                'id' => $run->schedule->id,
                'name' => $run->schedule->name,
                'edition_type' => $run->schedule->edition_type,
                'frequency_type' => $run->schedule->frequency_type,
                'run_frequency' => $run->schedule->run_frequency,
                'run_time' => $run->schedule->run_time ?: $run->schedule->scheduled_time,
                'timezone' => $run->schedule->timezone,
                'auto_generate_ai_response' => (bool) $run->schedule->auto_generate_ai_response,
                'auto_run_pipeline' => (bool) $run->schedule->auto_run_pipeline,
                'bulletin_type' => $run->schedule->bulletinType ? [
                    'id' => $run->schedule->bulletinType->id,
                    'name' => $run->schedule->bulletinType->name,
                    'location' => $run->schedule->bulletinType->location?->name,
                    'category' => $run->schedule->bulletinType->newsCategory?->name,
                    'language' => $run->schedule->bulletinType->language?->name,
                ] : null,
            ] : null,
            'provider' => $provider ? [
                'id' => $provider->id,
                'name' => $provider->name,
                'model' => $provider->default_model,
                'supports_grounding' => (bool) $provider->supports_grounding,
                'is_active' => (bool) $provider->is_active,
            ] : null,
            'script' => $run->script ? [
                'id' => $run->script->id,
                'title' => $run->script->title,
                'status' => $run->script->status,
            ] : null,
            'edition' => $run->edition ? [
                'id' => $run->edition->id,
                'title' => $run->edition->title,
                'language' => $run->edition->language,
                'scheduled_for' => optional($run->edition->scheduled_for)?->toIso8601String(),
            ] : null,
            'urls' => [
                'show' => $this->safeRoute('editor.editorial-schedule-runs.show', $run),
                'generate_prompt' => $this->safeRoute('editor.editorial-schedule-runs.generate-prompt', $run),
                'receive_response' => $this->safeRoute('editor.editorial-schedule-runs.receive-response', $run),
                'create_script' => $this->safeRoute('editor.editorial-schedule-runs.create-script', $run),
                'prompt_run' => $promptRun ? $this->safeRoute('editor.bulletin-prompt-runs.show', $promptRun) : null,
                'generate_ai_response' => $promptRun ? $this->safeRoute('editor.bulletin-prompt-runs.generate-ai-response', $promptRun) : null,
                'run_pipeline' => $promptRun ? $this->safeRoute('editor.bulletin-prompt-runs.run-pipeline', $promptRun) : null,
                'create_script_from_prompt_run' => $promptRun ? $this->safeRoute('editor.bulletin-prompt-runs.create-script', $promptRun) : null,
                'script' => $run->script_id || $promptRun?->script_id ? $this->safeRoute('editor.scripts.show', $run->script_id ?: $promptRun?->script_id) : null,
                'sources' => $this->safeRoute('editor.source-references.index'),
            ],
        ];
    }

    private function safeRoute(string $name, mixed $parameters = []): ?string
    {
        return Route::has($name) ? route($name, $parameters) : null;
    }
}
