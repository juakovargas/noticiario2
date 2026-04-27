<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\BulletinPromptRun;
use App\Models\BulletinType;
use App\Services\PromptGeneration\BulletinPromptRunService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BulletinPromptRunController extends Controller
{
    public function __construct(private readonly BulletinPromptRunService $service)
    {
    }

    public function index(): Response
    {
        return Inertia::render('Editor/BulletinPromptRuns/Index', [
            'runs' => BulletinPromptRun::query()
                ->with(['bulletinType:id,name', 'promptProfile:id,name', 'script:id,title,status'])
                ->latest()
                ->paginate(20),
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
        ]);

        return Inertia::render('Editor/BulletinPromptRuns/Show', ['run' => $bulletinPromptRun]);
    }

    public function store(BulletinType $bulletinType, Request $request): RedirectResponse
    {
        $run = $this->service->createFromBulletinType($bulletinType, $request->user()?->id);

        return to_route('editor.bulletin-prompt-runs.show', $run)->with('success', 'Prompt run created successfully.');
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

        return back()->with('success', 'AI response saved successfully.');
    }

    public function createScript(BulletinPromptRun $bulletinPromptRun): RedirectResponse
    {
        $script = $this->service->createScript($bulletinPromptRun);

        return to_route('editor.scripts.show', $script)->with('success', 'Script created from prompt run.');
    }
}
