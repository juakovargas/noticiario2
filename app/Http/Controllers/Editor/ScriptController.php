<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Editor\StoreScriptRequest;
use App\Http\Requests\Editor\UpdateScriptRequest;
use App\Models\Edition;
use App\Models\Script;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScriptController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Editor/Scripts/Index', [
            'scripts' => Script::query()
                ->with('edition:id,title')
                ->latest()
                ->paginate(10)
                ->through(fn (Script $script) => [
                    'id' => $script->id,
                    'title' => $script->title,
                    'edition' => $script->edition?->title,
                    'status' => $script->status,
                    'language' => $script->language,
                    'estimated_duration_seconds' => $script->estimated_duration_seconds,
                    'approved_at' => $script->approved_at?->toDateTimeString(),
                ]),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Editor/Scripts/Create', [
            ...$this->formOptions(),
            'selectedEditionId' => $request->query('edition_id'),
        ]);
    }

    public function store(StoreScriptRequest $request): RedirectResponse
    {
        Script::query()->create($request->validated());

        return to_route('editor.scripts.index')->with('success', 'Script created successfully.');
    }

    public function show(Script $script): Response
    {
        $script->load(['edition:id,title', 'approvedBy:id,name']);

        return Inertia::render('Editor/Scripts/Show', [
            'script' => [
                'id' => $script->id,
                'title' => $script->title,
                'edition' => $script->edition ? ['id' => $script->edition->id, 'title' => $script->edition->title] : null,
                'status' => $script->status,
                'language' => $script->language,
                'intro' => $script->intro,
                'body' => $script->body,
                'outro' => $script->outro,
                'estimated_duration_seconds' => $script->estimated_duration_seconds,
                'approved_at' => $script->approved_at?->toDateTimeString(),
                'approved_by' => $script->approvedBy?->name,
            ],
        ]);
    }

    public function edit(Script $script): Response
    {
        return Inertia::render('Editor/Scripts/Edit', [
            'script' => $script,
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateScriptRequest $request, Script $script): RedirectResponse
    {
        $script->update($request->validated());

        return to_route('editor.scripts.index')->with('success', 'Script updated successfully.');
    }

    public function destroy(Script $script): RedirectResponse
    {
        $script->delete();

        return to_route('editor.scripts.index')->with('success', 'Script deleted successfully.');
    }

    private function formOptions(): array
    {
        return [
            'editions' => Edition::query()->orderBy('title')->get(['id', 'title']),
            'statuses' => ['draft', 'review', 'approved', 'rejected', 'archived'],
        ];
    }
}
