<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\BulletinType;
use App\Models\Language;
use App\Models\Location;
use App\Models\NewsCategory;
use App\Models\PromptProfile;
use App\Support\GeneratesUniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BulletinTypeController extends Controller
{
    use GeneratesUniqueSlug;

    public function index(): Response
    {
        return Inertia::render('Editor/BulletinTypes/Index', [
            'bulletinTypes' => BulletinType::query()
                ->with(['location:id,name', 'newsCategory:id,name', 'language:id,name,code', 'promptProfile:id,name'])
                ->orderByDesc('is_active')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(20),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Editor/BulletinTypes/Create', $this->options());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug(BulletinType::class, $data['slug'] ?: $data['name']);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $type = BulletinType::query()->create($data);

        return to_route('editor.bulletin-types.show', $type)->with('success', 'Bulletin type created successfully.');
    }

    public function show(BulletinType $bulletinType): Response
    {
        $bulletinType->load(['location:id,name', 'newsCategory:id,name', 'language:id,name,code', 'promptProfile:id,name']);

        return Inertia::render('Editor/BulletinTypes/Show', ['bulletinType' => $bulletinType]);
    }

    public function edit(BulletinType $bulletinType): Response
    {
        return Inertia::render('Editor/BulletinTypes/Edit', [
            'bulletinType' => $bulletinType,
            ...$this->options(),
        ]);
    }

    public function update(Request $request, BulletinType $bulletinType): RedirectResponse
    {
        $data = $this->validated($request, $bulletinType);
        $data['slug'] = $this->uniqueSlug(BulletinType::class, $data['slug'] ?: $data['name'], $bulletinType->id);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $bulletinType->update($data);

        return to_route('editor.bulletin-types.show', $bulletinType)->with('success', 'Bulletin type updated successfully.');
    }

    public function destroy(BulletinType $bulletinType): RedirectResponse
    {
        $bulletinType->delete();

        return to_route('editor.bulletin-types.index')->with('success', 'Bulletin type deleted successfully.');
    }

    private function validated(Request $request, ?BulletinType $bulletinType = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('bulletin_types', 'slug')->ignore($bulletinType?->id)],
            'description' => ['nullable', 'string'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'news_category_id' => ['nullable', 'exists:news_categories,id'],
            'language_id' => ['nullable', 'exists:languages,id'],
            'default_prompt_profile_id' => ['nullable', 'exists:prompt_profiles,id'],
            'edition_type' => ['nullable', 'string', 'max:50'],
            'target_duration_seconds' => ['nullable', 'integer', 'min:15', 'max:3600'],
            'default_schedule_time' => ['nullable', 'date_format:H:i'],
            'default_timezone' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function options(): array
    {
        return [
            'locations' => Location::query()->orderBy('name')->get(['id', 'name']),
            'categories' => NewsCategory::query()->orderBy('name')->get(['id', 'name']),
            'languages' => Language::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'promptProfiles' => PromptProfile::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get(['id', 'name']),
            'editionTypes' => ['morning', 'afternoon', 'night', 'special'],
        ];
    }
}
