<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\EditorialSchedule;
use App\Models\EditorialScheduleRun;
use App\Services\EditorialScheduling\EditorialScheduleRunService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EditorialScheduleRunController extends Controller
{
    public function __construct(private readonly EditorialScheduleRunService $service)
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
            'schedule:id,name,edition_type,frequency_type',
            'edition:id,title,language,scheduled_for',
            'script:id,title,status',
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
            'runs' => $query->latest()->paginate(20)->withQueryString(),
            'filters' => $filters,
            'statuses' => ['pending', 'prompt_ready', 'waiting_ai_response', 'response_received', 'script_created', 'completed', 'failed', 'cancelled', 'archived'],
            'schedules' => EditorialSchedule::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(EditorialScheduleRun $editorialScheduleRun): Response
    {
        $editorialScheduleRun->load([
            'schedule.location:id,name', 'schedule.newsCategory:id,name', 'schedule.language:id,name,code',
            'edition:id,title,language,scheduled_for', 'script:id,title,status',
        ]);

        return Inertia::render('Editor/EditorialScheduleRuns/Show', [
            'run' => $editorialScheduleRun,
        ]);
    }

    public function generatePrompt(EditorialScheduleRun $editorialScheduleRun): RedirectResponse
    {
        $this->service->generatePrompt($editorialScheduleRun);

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
}
