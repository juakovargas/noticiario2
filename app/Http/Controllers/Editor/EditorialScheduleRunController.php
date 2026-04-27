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
        $query = EditorialScheduleRun::query()->with([
            'schedule:id,name,edition_type,frequency_type',
            'edition:id,title,language,scheduled_for',
            'script:id,title,status',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return Inertia::render('Editor/EditorialScheduleRuns/Index', [
            'runs' => $query->latest()->paginate(20)->withQueryString(),
            'filters' => $request->only('status'),
            'statuses' => ['pending', 'prompt_ready', 'waiting_ai_response', 'response_received', 'script_created', 'completed', 'failed', 'cancelled'],
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
}
