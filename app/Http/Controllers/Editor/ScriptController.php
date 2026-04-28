<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Editor\StoreScriptRequest;
use App\Http\Requests\Editor\UpdateScriptRequest;
use App\Models\Edition;
use App\Models\Language;
use App\Models\Script;
use App\Support\EditorialLanguage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScriptController extends Controller
{
    public function __construct(private readonly EditorialLanguage $editorialLanguage)
    {
    }

    public function index(Request $request): Response
    {
        $activeLanguages = $this->editorialLanguage->activeLanguageOptions();
        $languageDisplayMap = $activeLanguages->keyBy('code');

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
                ->with([
                    'edition:id,title',
                    'reviewedBy:id,name,email,profile_image_id',
                    'approvedBy:id,name,email,profile_image_id',
                    'rejectedBy:id,name,email,profile_image_id',
                ])
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
                    'language_display' => $languageDisplayMap->get($script->language),
                    'estimated_duration_seconds' => $script->estimated_duration_seconds,
                    'review_status' => $script->review_status ?? 'pending',
                    'reviewed_at' => $script->reviewed_at?->toDateTimeString(),
                    'reviewed_by' => $script->reviewedBy ? [
                        'id' => $script->reviewedBy->id,
                        'name' => $script->reviewedBy->name,
                        'email' => $script->reviewedBy->email,
                        'avatar_url' => $script->reviewedBy->avatar_url,
                        'initials' => $script->reviewedBy->initials,
                    ] : null,
                    'approved_at' => $script->approved_at?->toDateTimeString(),
                    'approved_by' => $script->approvedBy ? [
                        'id' => $script->approvedBy->id,
                        'name' => $script->approvedBy->name,
                        'email' => $script->approvedBy->email,
                        'avatar_url' => $script->approvedBy->avatar_url,
                        'initials' => $script->approvedBy->initials,
                    ] : null,
                    'rejected_at' => $script->rejected_at?->toDateTimeString(),
                    'rejected_by' => $script->rejectedBy ? [
                        'id' => $script->rejectedBy->id,
                        'name' => $script->rejectedBy->name,
                        'email' => $script->rejectedBy->email,
                        'avatar_url' => $script->rejectedBy->avatar_url,
                        'initials' => $script->rejectedBy->initials,
                    ] : null,
                ]),
            'filters' => $filters,
            'statuses' => ['draft', 'review', 'approved', 'rejected', 'archived'],
            'editions' => Edition::query()->orderBy('title')->get(['id', 'title']),
            'languages' => $activeLanguages->values(),
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
        $data = $request->validated();
        $edition = Edition::query()->with('location.defaultLanguage')->findOrFail($data['edition_id']);

        $data['language'] = $this->editorialLanguage->resolveCode($edition->location, $data['language'] ?? null);

        Script::query()->create($data);

        return to_route('editor.scripts.index')->with('success', 'Script created successfully.');
    }

    public function show(Script $script): Response
    {
        $script->load([
            'edition:id,title',
            'approvedBy:id,name,email,profile_image_id',
            'reviewedBy:id,name,email,profile_image_id',
            'rejectedBy:id,name,email,profile_image_id',
            'reviewItems',
            'sourceReferences',
        ]);

        $sourceCounts = $script->sourceReferences
            ->groupBy('verification_status')
            ->map->count();

        return Inertia::render('Editor/Scripts/Show', [
            'script' => [
                'id' => $script->id,
                'title' => $script->title,
                'edition' => $script->edition ? ['id' => $script->edition->id, 'title' => $script->edition->title] : null,
                'status' => $script->status,
                'language' => $script->language,
                'language_display' => Language::query()->where('code', $script->language)->first(['code', 'name', 'native_name', 'flag_emoji']),
                'intro' => $script->intro,
                'body' => $script->body,
                'outro' => $script->outro,
                'estimated_duration_seconds' => $script->estimated_duration_seconds,
                'review_status' => $script->review_status ?? 'pending',
                'reviewed_at' => $script->reviewed_at?->toDateTimeString(),
                'reviewed_by' => $script->reviewedBy ? [
                    'id' => $script->reviewedBy->id,
                    'name' => $script->reviewedBy->name,
                    'email' => $script->reviewedBy->email,
                    'avatar_url' => $script->reviewedBy->avatar_url,
                    'initials' => $script->reviewedBy->initials,
                ] : null,
                'approved_at' => $script->approved_at?->toDateTimeString(),
                'approved_by' => $script->approvedBy ? [
                    'id' => $script->approvedBy->id,
                    'name' => $script->approvedBy->name,
                    'email' => $script->approvedBy->email,
                    'avatar_url' => $script->approvedBy->avatar_url,
                    'initials' => $script->approvedBy->initials,
                ] : null,
                'rejected_at' => $script->rejected_at?->toDateTimeString(),
                'rejected_by' => $script->rejectedBy ? [
                    'id' => $script->rejectedBy->id,
                    'name' => $script->rejectedBy->name,
                    'email' => $script->rejectedBy->email,
                    'avatar_url' => $script->rejectedBy->avatar_url,
                    'initials' => $script->rejectedBy->initials,
                ] : null,
                'rejection_reason' => $script->rejection_reason,
                'review_items_count' => $script->reviewItems->count(),
                'source_summary' => [
                    'total' => $script->sourceReferences->count(),
                    'verified' => $sourceCounts->get('verified', 0),
                    'pending' => $sourceCounts->get('pending', 0),
                    'weak' => $sourceCounts->get('weak', 0),
                    'issues' => (int) ($sourceCounts->get('missing', 0) + $sourceCounts->get('broken', 0) + $sourceCounts->get('rejected', 0)),
                ],
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
        $data = $request->validated();
        $editionId = $data['edition_id'] ?? $script->edition_id;
        $edition = Edition::query()->with('location.defaultLanguage')->findOrFail($editionId);

        $data['language'] = $this->editorialLanguage->resolveCode($edition->location, $data['language'] ?? null);

        $script->update($data);

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
            'editions' => Edition::query()
                ->with(['location.defaultLanguage:id,code'])
                ->orderBy('title')
                ->get(['id', 'title', 'location_id'])
                ->map(fn (Edition $edition) => [
                    'id' => $edition->id,
                    'title' => $edition->title,
                    'default_language_code' => $edition->location?->defaultLanguage?->code,
                ]),
            'statuses' => ['draft', 'review', 'approved', 'rejected', 'archived'],
            'languages' => $this->editorialLanguage->activeLanguageOptions()->values(),
        ];
    }
}
