<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Editor\StoreEditionRequest;
use App\Http\Requests\Editor\UpdateEditionRequest;
use App\Models\Language;
use App\Models\Location;
use App\Models\NewsItem;
use App\Models\Edition;
use App\Support\EditorialLanguage;
use App\Support\GeneratesUniqueSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EditionController extends Controller
{
    use GeneratesUniqueSlug;

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
            'edition_type' => (string) $request->query('edition_type', ''),
            'location_id' => (string) $request->query('location_id', ''),
            'language' => (string) $request->query('language', ''),
            'scheduled_from' => (string) $request->query('scheduled_from', ''),
            'scheduled_to' => (string) $request->query('scheduled_to', ''),
            'sort' => (string) $request->query('sort', 'scheduled_for'),
            'direction' => (string) $request->query('direction', 'desc'),
            'show_archived' => $request->boolean('show_archived'),
        ];

        $allowedSorts = ['title', 'status', 'edition_type', 'scheduled_for', 'target_duration_seconds', 'created_at'];
        $sort = in_array($filters['sort'], $allowedSorts, true) ? $filters['sort'] : 'scheduled_for';
        $direction = in_array($filters['direction'], ['asc', 'desc'], true) ? $filters['direction'] : 'desc';

        $editions = Edition::query()
            ->when(! $filters['show_archived'], fn (Builder $query) => $query->where('status', '!=', 'archived'))
            ->with('location:id,name,country_code,type')
            ->withCount(['newsItems', 'scripts'])
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = $filters['search'];
                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] !== '', fn (Builder $query) => $query->where('status', $filters['status']))
            ->when($filters['edition_type'] !== '', fn (Builder $query) => $query->where('edition_type', $filters['edition_type']))
            ->when($filters['location_id'] !== '', fn (Builder $query) => $query->where('location_id', $filters['location_id']))
            ->when($filters['language'] !== '', fn (Builder $query) => $query->where('language', $filters['language']))
            ->when($filters['scheduled_from'] !== '', fn (Builder $query) => $query->whereDate('scheduled_for', '>=', $filters['scheduled_from']))
            ->when($filters['scheduled_to'] !== '', fn (Builder $query) => $query->whereDate('scheduled_for', '<=', $filters['scheduled_to']))
            ->orderByRaw($sort === 'scheduled_for' ? 'scheduled_for is null asc' : '0 asc')
            ->orderBy($sort, $direction)
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Edition $edition) => [
                'id' => $edition->id,
                'title' => $edition->title,
                'edition_type' => $edition->edition_type,
                'location' => $edition->location,
                'scheduled_for' => $edition->scheduled_for?->toDateTimeString(),
                'language' => $edition->language,
                'language_display' => $languageDisplayMap->get($edition->language),
                'status' => $edition->status,
                'target_duration_seconds' => $edition->target_duration_seconds,
                'news_items_count' => $edition->news_items_count,
                'scripts_count' => $edition->scripts_count,
            ]);

        return Inertia::render('Editor/Editions/Index', [
            'editions' => $editions,
            'filters' => $filters,
            'statuses' => ['draft', 'planning', 'scripting', 'approved', 'archived'],
            'editionTypes' => ['morning', 'afternoon', 'night', 'special'],
            'languages' => $activeLanguages->values(),
            'locations' => Location::query()->orderBy('name')->get(['id', 'name', 'country_code', 'type']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Editor/Editions/Create', $this->formOptions());
    }

    public function store(StoreEditionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug(Edition::class, $data['slug'] ?: $data['title']);

        $location = ! empty($data['location_id']) ? Location::query()->with('defaultLanguage')->find($data['location_id']) : null;
        $data['language'] = $this->editorialLanguage->resolveCode($location, $data['language'] ?? null);

        Edition::query()->create($data);

        return to_route('editor.editions.index')->with('success', 'Edition created successfully.');
    }

    public function show(Edition $edition): Response
    {
        $locale = app()->getLocale();

        $edition->load([
            'location:id,name,country_code',
            'newsItems' => fn ($query) => $query
                ->where('status', '!=', 'archived')
                ->with(['source:id,name', 'category:id,name', 'category.translations:id,news_category_id,language_code,name', 'location:id,name,country_code'])
                ->orderBy('edition_news_item.sort_order'),
            'scripts:id,edition_id,title,status,language,estimated_duration_seconds,approved_at,approved_by',
        ]);

        $selectedNewsItemIds = $edition->newsItems->pluck('id');
        $languageDisplay = Language::query()->where('code', $edition->language)->first(['code', 'name', 'native_name', 'flag_emoji']);

        return Inertia::render('Editor/Editions/Show', [
            'edition' => [
                'id' => $edition->id,
                'title' => $edition->title,
                'edition_type' => $edition->edition_type,
                'location' => $edition->location,
                'scheduled_for' => $edition->scheduled_for?->toDateTimeString(),
                'language' => $edition->language,
                'language_display' => $languageDisplay,
                'status' => $edition->status,
                'target_duration_seconds' => $edition->target_duration_seconds,
                'description' => $edition->description,
            ],
            'newsItems' => $edition->newsItems->map(fn (NewsItem $item) => [
                'id' => $item->id,
                'title' => $item->title,
                'summary' => $item->summary,
                'source' => $item->source?->name,
                'category' => $item->category?->displayName($locale),
                'location' => $item->location,
                'status' => $item->status,
                'sort_order' => $item->pivot->sort_order,
                'editorial_angle' => $item->pivot->editorial_angle,
                'included_in_script' => (bool) $item->pivot->included_in_script,
            ])->values(),
            'availableNewsItems' => NewsItem::query()
                ->whereNotIn('id', $selectedNewsItemIds)
                ->orderBy('title')
                ->get(['id', 'title']),
            'scripts' => $edition->scripts->map(fn ($script) => [
                'id' => $script->id,
                'title' => $script->title,
                'status' => $script->status,
                'language' => $script->language,
                'language_display' => Language::query()->where('code', $script->language)->first(['code', 'name', 'native_name', 'flag_emoji']),
                'estimated_duration_seconds' => $script->estimated_duration_seconds,
            ])->values(),
        ]);
    }

    public function edit(Edition $edition): Response
    {
        return Inertia::render('Editor/Editions/Edit', [
            'edition' => $edition,
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateEditionRequest $request, Edition $edition): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug(Edition::class, $data['slug'] ?: $data['title'], $edition->id);

        $locationId = $data['location_id'] ?? $edition->location_id;
        $location = $locationId ? Location::query()->with('defaultLanguage')->find($locationId) : null;
        $data['language'] = $this->editorialLanguage->resolveCode($location, $data['language'] ?? null);

        $edition->update($data);

        return to_route('editor.editions.index')->with('success', 'Edition updated successfully.');
    }

    public function destroy(Edition $edition): RedirectResponse
    {
        $edition->delete();

        return to_route('editor.editions.index')->with('success', 'Edition deleted successfully.');
    }

    public function archive(Edition $edition): RedirectResponse
    {
        if ($edition->status !== 'archived') {
            $metadata = is_array($edition->metadata) ? $edition->metadata : [];
            $metadata['previous_status'] = $edition->status;

            $edition->update([
                'status' => 'archived',
                'metadata' => $metadata,
            ]);
        }

        return back()->with('success', 'Edition archived successfully.');
    }

    public function restore(Edition $edition): RedirectResponse
    {
        $metadata = is_array($edition->metadata) ? $edition->metadata : [];
        $previousStatus = $metadata['previous_status'] ?? null;
        $safeStatuses = ['draft', 'planning', 'scripting', 'approved'];
        $status = in_array($previousStatus, $safeStatuses, true) ? $previousStatus : 'planning';

        unset($metadata['previous_status']);

        $edition->update([
            'status' => $status,
            'metadata' => $metadata,
        ]);

        return back()->with('success', 'Edition restored successfully.');
    }

    private function formOptions(): array
    {
        return [
            'locations' => Location::query()
                ->with('defaultLanguage:id,code')
                ->orderBy('name')
                ->get(['id', 'name', 'default_language_id'])
                ->map(fn (Location $location) => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'default_language_code' => $location->defaultLanguage?->code,
                ]),
            'types' => ['morning', 'afternoon', 'night', 'special'],
            'statuses' => ['draft', 'planning', 'scripting', 'approved', 'archived'],
            'languages' => $this->editorialLanguage->activeLanguageOptions()->values(),
        ];
    }
}
