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

    public function index(Request $request): Response
    {
        $filters = [
            'location_id' => (string) $request->query('location_id', ''),
        ];

        return Inertia::render('Editor/BulletinTypes/Index', [
            'bulletinTypes' => BulletinType::query()
                ->with(['location:id,name', 'newsCategory:id,name', 'language:id,name,code', 'promptProfile:id,name'])
                ->when($filters['location_id'] !== '', fn ($query) => $query->where('location_id', $filters['location_id']))
                ->orderByDesc('is_active')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Editor/BulletinTypes/Create', $this->options());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->enrichValidatedData($request, $this->validated($request));
        $data['slug'] = $this->uniqueSlug(BulletinType::class, $data['slug'] ?: $data['name']);

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
        $data = $this->enrichValidatedData($request, $this->validated($request, $bulletinType));
        $data['slug'] = $this->uniqueSlug(BulletinType::class, $data['slug'] ?: $data['name'], $bulletinType->id);

        $bulletinType->update($data);

        return to_route('editor.bulletin-types.show', $bulletinType)->with('success', 'Bulletin type updated successfully.');
    }

    public function destroy(BulletinType $bulletinType): RedirectResponse
    {
        $bulletinType->delete();

        return to_route('editor.bulletin-types.index')->with('success', 'Bulletin type deleted successfully.');
    }

    private function enrichValidatedData(Request $request, array $data): array
    {
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['coverage_mode'] = $data['coverage_mode'] ?? 'previous_period';
        $data['prompt_language'] = $data['prompt_language'] ?? 'es';
        $data['output_mode'] = $data['output_mode'] ?? 'structured_script';
        $data['include_future_agenda'] = $request->boolean('include_future_agenda');
        $data['include_historical_context'] = $request->boolean('include_historical_context');

        if (isset($data['min_news_items'], $data['max_news_items']) && $data['min_news_items'] > $data['max_news_items']) {
            [$data['min_news_items'], $data['max_news_items']] = [$data['max_news_items'], $data['min_news_items']];
        }

        return $data;
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
            'coverage_mode' => ['nullable', Rule::in(['previous_period', 'today_so_far', 'yesterday', 'last_24_hours', 'next_24_hours', 'custom', 'none'])],
            'coverage_starts_offset_minutes' => ['nullable', 'integer', 'min:-10080', 'max:10080'],
            'coverage_ends_offset_minutes' => ['nullable', 'integer', 'min:-10080', 'max:10080'],
            'coverage_description' => ['nullable', 'string'],
            'include_future_agenda' => ['boolean'],
            'include_historical_context' => ['boolean'],
            'min_news_items' => ['nullable', 'integer', 'min:1', 'max:50'],
            'max_news_items' => ['nullable', 'integer', 'min:1', 'max:50'],
            'prompt_language' => ['nullable', Rule::in(['es', 'en', 'fr'])],
            'output_mode' => ['nullable', Rule::in(['plain_script', 'structured_script'])],
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
            'coverageModes' => ['previous_period', 'today_so_far', 'yesterday', 'last_24_hours', 'next_24_hours', 'custom', 'none'],
            'promptLanguages' => ['es', 'en', 'fr'],
            'outputModes' => ['plain_script', 'structured_script'],
        ];
    }
}
