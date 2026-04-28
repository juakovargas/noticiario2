<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\BulletinPromptRun;
use App\Models\BulletinType;
use App\Models\PromptProfile;
use App\Models\User;
use App\Services\PromptGeneration\BulletinCoverageWindowResolver;
use App\Services\PromptGeneration\BulletinPromptRunService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BulletinPromptRunController extends Controller
{
    public function __construct(
        private readonly BulletinPromptRunService $service,
        private readonly BulletinCoverageWindowResolver $coverageWindowResolver,
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = [
            'search' => (string) $request->query('search', ''),
            'status' => (string) $request->query('status', ''),
            'bulletin_type_id' => (string) $request->query('bulletin_type_id', ''),
            'prompt_profile_id' => (string) $request->query('prompt_profile_id', ''),
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
                'script:id,title,status',
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
            'script:id,title,status',
            'createdBy:id,name,email,profile_image_id',
        ]);

        return Inertia::render('Editor/BulletinPromptRuns/Show', [
            'run' => $bulletinPromptRun,
            'promptContext' => $this->coverageWindowResolver->resolve($bulletinPromptRun),
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
