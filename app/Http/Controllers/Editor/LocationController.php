<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Editor\StoreLocationRequest;
use App\Http\Requests\Editor\UpdateLocationRequest;
use App\Models\Language;
use App\Models\Location;
use App\Support\GeneratesUniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    use GeneratesUniqueSlug;

    public function index(Request $request): Response
    {
        $filters = [
            'show_on_map' => (string) $request->query('show_on_map', ''),
            'has_coordinates' => (string) $request->query('has_coordinates', ''),
        ];

        $locations = Location::query()
            ->with(['parent:id,name', 'defaultLanguage:id,code,name,native_name,flag_emoji'])
            ->when($filters['show_on_map'] !== '', fn ($query) => $query->where('show_on_map', $filters['show_on_map'] === '1'))
            ->when($filters['has_coordinates'] === '1', fn ($query) => $query->whereNotNull('latitude')->whereNotNull('longitude'))
            ->when($filters['has_coordinates'] === '0', fn ($query) => $query->where(function ($sub): void {
                $sub->whereNull('latitude')->orWhereNull('longitude');
            }))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Location $location) => [
                'id' => $location->id,
                'name' => $location->name,
                'parent' => $location->parent?->name,
                'type' => $location->type,
                'country_code' => $location->country_code,
                'is_active' => $location->is_active,
                'sort_order' => $location->sort_order,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'marker_color' => $location->marker_color,
                'marker_label' => $location->marker_label,
                'show_on_map' => $location->show_on_map,
                'default_language' => $location->defaultLanguage ? [
                    'code' => $location->defaultLanguage->code,
                    'name' => $location->defaultLanguage->name,
                    'native_name' => $location->defaultLanguage->native_name,
                    'flag_emoji' => $location->defaultLanguage->flag_emoji,
                ] : null,
            ]);

        return Inertia::render('Editor/Locations/Index', [
            'locations' => $locations,
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Editor/Locations/Create', [
            'parents' => Location::query()->orderBy('name')->get(['id', 'name']),
            'types' => ['global', 'country', 'region', 'city', 'custom'],
            'languages' => $this->activeLanguages(),
        ]);
    }

    public function store(StoreLocationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug(Location::class, $data['slug'] ?: $data['name']);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['show_on_map'] = $request->boolean('show_on_map', true);
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
            'languages' => $this->activeLanguages(),
        ]);
    }

    public function update(UpdateLocationRequest $request, Location $location): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug(Location::class, $data['slug'] ?: $data['name'], $location->id);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['show_on_map'] = $request->boolean('show_on_map', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $location->update($data);

        return to_route('editor.locations.index')->with('success', 'Location updated successfully.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        $location->delete();

        return to_route('editor.locations.index')->with('success', 'Location deleted successfully.');
    }

    private function activeLanguages()
    {
        return Language::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'native_name', 'flag_emoji']);
    }
}
