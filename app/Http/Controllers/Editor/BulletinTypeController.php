<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\AiProvider;
use App\Models\BulletinType;
use App\Models\Language;
use App\Models\Location;
use App\Models\NewsCategory;
use App\Models\PromptProfile;
use App\Services\Scheduling\BulletinTypeScheduleSyncService;
use App\Support\GeneratesUniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BulletinTypeController extends Controller
{
    public function __construct(private readonly BulletinTypeScheduleSyncService $scheduleSyncService)
    {
    }

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
        $this->scheduleSyncService->syncPrimarySchedule($type);

        return to_route('editor.bulletin-types.show', $type)->with('success', 'Bulletin type created successfully. Primary schedule synchronized.');
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
        $this->scheduleSyncService->syncPrimarySchedule($bulletinType->refresh());

        return to_route('editor.bulletin-types.show', $bulletinType)->with('success', 'Bulletin type updated successfully. Primary schedule synchronized.');
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
        $data['default_run_frequency'] = $data['default_run_frequency'] ?? 'daily';
        $data['default_run_time'] = $data['default_run_time'] ?? $data['default_schedule_time'] ?? null;
        $data['default_schedule_is_active'] = $request->boolean('default_schedule_is_active');
        $data['default_auto_run_pipeline'] = $request->boolean('default_auto_run_pipeline');
        $data['default_auto_generate_ai_response'] = $request->boolean('default_auto_generate_ai_response');
        $data['default_auto_create_script'] = $request->boolean('default_auto_create_script', true);
        $data['default_auto_generate_metadata'] = $request->boolean('default_auto_generate_metadata', true);
        $data['default_auto_extract_sources'] = $request->boolean('default_auto_extract_sources', true);
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
            'ai_provider_id' => ['nullable', 'exists:ai_providers,id'],
            'edition_type' => ['nullable', 'string', 'max:50'],
            'target_duration_seconds' => ['nullable', 'integer', 'min:15', 'max:3600'],
            'default_schedule_time' => ['nullable', 'date_format:H:i'],
            'default_timezone' => ['nullable', 'string', 'max:100'],
            'default_run_frequency' => ['nullable', Rule::in(['daily','weekdays','weekends','selected_days','monthly','custom'])],
            'default_run_time' => ['nullable', 'date_format:H:i'],
            'default_run_days' => ['nullable', 'array'],
            'default_run_days.*' => ['string'],
            'default_schedule_is_active' => ['boolean'],
            'default_auto_run_pipeline' => ['boolean'],
            'default_auto_generate_ai_response' => ['boolean'],
            'default_auto_create_script' => ['boolean'],
            'default_auto_generate_metadata' => ['boolean'],
            'default_auto_extract_sources' => ['boolean'],
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
            'scriptProviders' => AiProvider::query()->where('is_active', true)->where('is_testing', false)->where(function ($q): void {
                $q->where('provider_category', 'text')->orWhere('provider_category', 'grounded_text')->orWhereJsonContains('capabilities', 'script_generation')->orWhereJsonContains('capabilities', 'news_grounding');
            })->orderByDesc('is_default')->orderBy('name')->get(['id','name','provider_category','capabilities']),
            'editionTypes' => ['morning', 'afternoon', 'night', 'special'],
            'coverageModes' => ['previous_period', 'today_so_far', 'yesterday', 'last_24_hours', 'next_24_hours', 'custom', 'none'],
            'promptLanguages' => ['es', 'en', 'fr'],
            'outputModes' => ['plain_script', 'structured_script'],
        ];
    }
}
