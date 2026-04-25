<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Editor\StoreEditionRequest;
use App\Http\Requests\Editor\UpdateEditionRequest;
use App\Models\Edition;
use App\Models\Location;
use App\Models\NewsItem;
use App\Support\GeneratesUniqueSlug;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EditionController extends Controller
{
    use GeneratesUniqueSlug;

    public function index(): Response
    {
        return Inertia::render('Editor/Editions/Index', [
            'editions' => Edition::query()
                ->with('location:id,name,country_code')
                ->latest('scheduled_for')
                ->paginate(10)
                ->through(fn (Edition $edition) => [
                    'id' => $edition->id,
                    'title' => $edition->title,
                    'edition_type' => $edition->edition_type,
                    'location' => $edition->location,
                    'scheduled_for' => $edition->scheduled_for?->toDateTimeString(),
                    'language' => $edition->language,
                    'status' => $edition->status,
                    'target_duration_seconds' => $edition->target_duration_seconds,
                ]),
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

        Edition::query()->create($data);

        return to_route('editor.editions.index')->with('success', 'Edition created successfully.');
    }

    public function show(Edition $edition): Response
    {
        $locale = app()->getLocale();

        $edition->load([
            'location:id,name,country_code',
            'newsItems' => fn ($query) => $query
                ->with(['source:id,name', 'category:id,name', 'category.translations:id,news_category_id,language_code,name', 'location:id,name,country_code'])
                ->orderBy('edition_news_item.sort_order'),
            'scripts:id,edition_id,title,status,language,estimated_duration_seconds,approved_at,approved_by',
        ]);

        $selectedNewsItemIds = $edition->newsItems->pluck('id');

        return Inertia::render('Editor/Editions/Show', [
            'edition' => [
                'id' => $edition->id,
                'title' => $edition->title,
                'edition_type' => $edition->edition_type,
                'location' => $edition->location,
                'scheduled_for' => $edition->scheduled_for?->toDateTimeString(),
                'language' => $edition->language,
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

        $edition->update($data);

        return to_route('editor.editions.index')->with('success', 'Edition updated successfully.');
    }

    public function destroy(Edition $edition): RedirectResponse
    {
        $edition->delete();

        return to_route('editor.editions.index')->with('success', 'Edition deleted successfully.');
    }

    private function formOptions(): array
    {
        return [
            'locations' => Location::query()->orderBy('name')->get(['id', 'name']),
            'types' => ['morning', 'afternoon', 'night', 'special'],
            'statuses' => ['draft', 'planning', 'scripting', 'approved', 'archived'],
        ];
    }
}
