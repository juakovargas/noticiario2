<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Editor\StoreScriptRequest;
use App\Http\Requests\Editor\UpdateScriptRequest;
use App\Models\Edition;
use App\Models\Language;
use App\Models\Script;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScriptController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => (string) $request->query('search', ''),
            'status' => (string) $request->query('status', ''),
            'edition_id' => (string) $request->query('edition_id', ''),
            'language' => (string) $request->query('language', ''),
            'approved' => (string) $request->query('approved', ''),
            'sort' => (string) $request->query('sort', 'created_at'),
            'direction' => (string) $request->query('direction', 'desc'),
        ];

        $allowedSorts = ['title', 'status', 'language', 'estimated_duration_seconds', 'approved_at', 'created_at'];
        $sort = in_array($filters['sort'], $allowedSorts, true) ? $filters['sort'] : 'created_at';
        $direction = in_array($filters['direction'], ['asc', 'desc'], true) ? $filters['direction'] : 'desc';

        return Inertia::render('Editor/Scripts/Index', [
            'scripts' => Script::query()
                ->with('edition:id,title')
                ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                    $search = $filters['search'];
                    $query->where(function (Builder $subQuery) use ($search): void {
                        $subQuery
                            ->where('title', 'like', "%{$search}%")
                            ->orWhere('intro', 'like', "%{$search}%")
                            ->orWhere('body', 'like', "%{$search}%")
                            ->orWhere('outro', 'like', "%{$search}%");
                    });
                })
                ->when($filters['status'] !== '', fn (Builder $query) => $query->where('status', $filters['status']))
                ->when($filters['edition_id'] !== '', fn (Builder $query) => $query->where('edition_id', $filters['edition_id']))
                ->when($filters['language'] !== '', fn (Builder $query) => $query->where('language', $filters['language']))
                ->when($filters['approved'] === 'yes', fn (Builder $query) => $query->whereNotNull('approved_at'))
                ->when($filters['approved'] === 'no', fn (Builder $query) => $query->whereNull('approved_at'))
                ->orderBy($sort, $direction)
                ->orderByDesc('created_at')
                ->paginate(15)
                ->withQueryString()
                ->through(fn (Script $script) => [
                    'id' => $script->id,
                    'title' => $script->title,
                    'edition' => $script->edition?->title,
                    'edition_id' => $script->edition_id,
                    'status' => $script->status,
                    'language' => $script->language,
                    'estimated_duration_seconds' => $script->estimated_duration_seconds,
                    'approved_at' => $script->approved_at?->toDateTimeString(),
                ]),
            'filters' => $filters,
            'statuses' => ['draft', 'review', 'approved', 'rejected', 'archived'],
            'editions' => Edition::query()->orderBy('title')->get(['id', 'title']),
            'languages' => Language::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->pluck('code'),
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
