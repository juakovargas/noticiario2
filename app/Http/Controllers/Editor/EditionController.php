<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Editor\StoreEditionRequest;
use App\Http\Requests\Editor\UpdateEditionRequest;
use App\Models\Edition;
use App\Models\Location;
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
                ->with('location:id,name')
                ->latest('scheduled_for')
                ->paginate(10)
                ->through(fn (Edition $edition) => [
                    'id' => $edition->id,
                    'title' => $edition->title,
                    'edition_type' => $edition->edition_type,
                    'location' => $edition->location?->name,
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
        $edition->load(['location:id,name', 'newsItems:id,title']);

        return Inertia::render('Editor/Editions/Show', [
            'edition' => [
                'id' => $edition->id,
                'title' => $edition->title,
                'edition_type' => $edition->edition_type,
                'location' => $edition->location?->name,
                'scheduled_for' => $edition->scheduled_for?->toDateTimeString(),
                'language' => $edition->language,
                'status' => $edition->status,
                'target_duration_seconds' => $edition->target_duration_seconds,
                'description' => $edition->description,
            ],
            'newsItems' => $edition->newsItems->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->title,
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
