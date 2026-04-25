<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Editor\StoreLocationRequest;
use App\Http\Requests\Editor\UpdateLocationRequest;
use App\Models\Location;
use App\Support\GeneratesUniqueSlug;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    use GeneratesUniqueSlug;

    public function index(): Response
    {
        return Inertia::render('Editor/Locations/Index', [
            'locations' => Location::query()
                ->with('parent:id,name')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(10)
                ->through(fn (Location $location) => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'parent' => $location->parent?->name,
                    'type' => $location->type,
                    'country_code' => $location->country_code,
                    'is_active' => $location->is_active,
                    'sort_order' => $location->sort_order,
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Editor/Locations/Create', [
            'parents' => Location::query()->orderBy('name')->get(['id', 'name']),
            'types' => ['global', 'country', 'region', 'city', 'custom'],
        ]);
    }

    public function store(StoreLocationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug(Location::class, $data['slug'] ?: $data['name']);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        Location::query()->create($data);

        return to_route('editor.locations.index')->with('success', 'Location created successfully.');
    }

    public function edit(Location $location): Response
    {
        return Inertia::render('Editor/Locations/Edit', [
            'location' => $location,
            'parents' => Location::query()->whereKeyNot($location->id)->orderBy('name')->get(['id', 'name']),
            'types' => ['global', 'country', 'region', 'city', 'custom'],
        ]);
    }

    public function update(UpdateLocationRequest $request, Location $location): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug(Location::class, $data['slug'] ?: $data['name'], $location->id);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $location->update($data);

        return to_route('editor.locations.index')->with('success', 'Location updated successfully.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        $location->delete();

        return to_route('editor.locations.index')->with('success', 'Location deleted successfully.');
    }
}
