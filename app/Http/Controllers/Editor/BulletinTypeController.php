<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\AiProvider;
use App\Models\BulletinPromptRun;
use App\Models\BulletinType;
use App\Models\EditorialSchedule;
use App\Models\EditorialScheduleRun;
use App\Models\Language;
use App\Models\Location;
use App\Models\NewsCategory;
use App\Models\PromptProfile;
use App\Models\Script;
use App\Services\Editor\BulletinTypeActivationService;
use App\Services\PromptGeneration\BulletinPromptGenerator;
use App\Services\Scheduling\BulletinTypeScheduleSyncService;
use App\Support\GeneratesUniqueSlug;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BulletinTypeController extends Controller
{
    public function __construct(
        private readonly BulletinTypeScheduleSyncService $scheduleSyncService,
        private readonly BulletinPromptGenerator $promptGenerator,
        private readonly BulletinTypeActivationService $activationService,
    )
    {
    }

    use GeneratesUniqueSlug;

    public function index(Request $request): Response
    {
        $filters = [
            'location_id' => (string) $request->query('location_id', ''),
            'provider' => (string) $request->query('provider', ''),
            'active' => (string) $request->query('active', ''),
            'health' => (string) $request->query('health', ''),
            'language_id' => (string) $request->query('language_id', ''),
            'category_id' => (string) $request->query('category_id', ''),
        ];

        $providerOptions = $this->scriptProviderQuery()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'default_model', 'supports_grounding']);

        $rows = BulletinType::query()
            ->with([
                'location:id,name',
                'newsCategory:id,name',
                'language:id,name,code',
                'promptProfile:id,name',
                'preferredAiProvider:id,name,slug,default_model,supports_grounding,provider_category,is_active',
                'primarySchedule:id,bulletin_type_id,is_primary,run_frequency,run_time,scheduled_time,scheduled_date,timezone,is_active,next_run_at,last_run_at,auto_generate_ai_response,auto_run_pipeline,metadata',
                'schedules:id,bulletin_type_id,is_primary,run_frequency,run_time,scheduled_time,scheduled_date,timezone,is_active,next_run_at,last_run_at,auto_generate_ai_response,auto_run_pipeline,metadata',
            ])
            ->when($filters['location_id'] !== '', fn ($query) => $query->where('location_id', $filters['location_id']))
            ->when($filters['language_id'] !== '', fn ($query) => $query->where('language_id', $filters['language_id']))
            ->when($filters['category_id'] !== '', fn ($query) => $query->where('news_category_id', $filters['category_id']))
            ->when($filters['provider'] === 'missing', fn ($query) => $query->whereNull('preferred_ai_provider_id'))
            ->when(is_numeric($filters['provider']), fn ($query) => $query->where('preferred_ai_provider_id', (int) $filters['provider']))
            ->when($filters['health'] === 'missing_provider', fn ($query) => $query->whereNull('preferred_ai_provider_id'))
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (BulletinType $bulletinType) => $this->presentBulletinIndexRow($bulletinType))
            ->values();

        if (in_array($filters['active'], ['active', '1'], true)) {
            $rows = $rows->filter(fn (array $row) => (bool) $row['is_on'])->values();
        } elseif (in_array($filters['active'], ['inactive', '0'], true)) {
            $rows = $rows->filter(fn (array $row) => ! (bool) $row['is_on'])->values();
        }

        if ($filters['health'] === 'missing_schedule') {
            $rows = $rows->filter(fn (array $row) => (int) data_get($row, 'schedule_summary.active', 0) === 0)->values();
        }

        return Inertia::render('Editor/BulletinTypes/Index', [
            'bulletinTypes' => [
                'data' => $rows,
                'links' => [],
            ],
            'activeBulletins' => $rows->where('is_on', true)->values(),
            'inactiveBulletins' => $rows->where('is_on', false)->values(),
            'filters' => $filters,
            'locations' => Location::query()->orderBy('name')->get(['id', 'name']),
            'providerOptions' => $providerOptions,
            'languages' => Language::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'categories' => NewsCategory::query()->orderBy('name')->get(['id', 'name']),
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
        $this->scheduleSyncService->syncSchedules($type->refresh());

        if (! $type->refresh()->is_active) {
            $this->activationService->deactivate($type);
        }

        if ($type->refresh()->is_active && ! $this->activationService->isComplete($type)) {
            $this->activationService->deactivate($type);

            return to_route('editor.bulletin-types.show', $type)->with('error', 'flash.bulletinCannotActivate');
        }

        return to_route('editor.bulletin-types.show', $type)->with('success', 'flash.bulletinCreated');
    }

    public function show(BulletinType $bulletinType): Response
    {
        $bulletinType->load([
            'location:id,name',
            'newsCategory:id,name',
            'language:id,name,code',
            'promptProfile:id,name',
            'preferredAiProvider:id,name,slug,default_model,supports_grounding,provider_category,is_active',
            'schedules' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('run_time')->orderBy('scheduled_time'),
            'primarySchedule',
        ]);

        return Inertia::render('Editor/BulletinTypes/Show', [
            'bulletinType' => $bulletinType,
            'primarySchedule' => $bulletinType->primarySchedule,
            'schedules' => $this->configuredSchedules($bulletinType)
                ->map(fn (EditorialSchedule $schedule) => $this->presentScheduleRow($schedule))
                ->values(),
            'scheduleSummary' => $this->scheduleSummary($bulletinType),
            'recentExecutions' => $this->recentExecutions($bulletinType),
            'recentScripts' => $this->recentScripts($bulletinType),
            'promptPreview' => $this->promptPreview($bulletinType),
            'health' => $this->bulletinHealth($bulletinType),
        ]);
    }

    public function edit(BulletinType $bulletinType): Response
    {
        $bulletinType->load([
            'schedules' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('run_time')->orderBy('scheduled_time'),
        ]);

        return Inertia::render('Editor/BulletinTypes/Edit', [
            'bulletinType' => $bulletinType,
            'scheduleConfig' => $this->scheduleConfig($bulletinType),
            ...$this->options(),
        ]);
    }

    public function update(Request $request, BulletinType $bulletinType): RedirectResponse
    {
        $data = $this->enrichValidatedData($request, $this->validated($request, $bulletinType));
        $data['slug'] = $this->uniqueSlug(BulletinType::class, $data['slug'] ?: $data['name'], $bulletinType->id);

        $bulletinType->update($data);
        $this->scheduleSyncService->syncSchedules($bulletinType->refresh());

        if (! $bulletinType->refresh()->is_active) {
            $this->activationService->deactivate($bulletinType);
        }

        if ($bulletinType->refresh()->is_active && ! $this->activationService->isComplete($bulletinType)) {
            $this->activationService->deactivate($bulletinType);

            return to_route('editor.bulletin-types.show', $bulletinType)->with('error', 'flash.bulletinCannotActivate');
        }

        return to_route('editor.bulletin-types.show', $bulletinType)->with('success', 'flash.bulletinUpdated');
    }

    public function toggleActive(BulletinType $bulletinType): RedirectResponse
    {
        $bulletinType->load(['preferredAiProvider', 'location', 'newsCategory', 'language', 'primarySchedule', 'schedules']);

        if ($this->activationService->isRunnable($bulletinType)) {
            $this->activationService->deactivate($bulletinType);

            return back()->with('success', 'flash.bulletinTurnedOff');
        }

        if (! $bulletinType->primarySchedule && $this->scheduleSyncService->shouldHavePrimarySchedule($bulletinType)) {
            $this->scheduleSyncService->syncSchedules($bulletinType);
            $bulletinType->refresh()->load(['preferredAiProvider', 'location', 'newsCategory', 'language', 'schedules']);
        }

        $missing = $this->activationService->missingConfiguration($bulletinType);

        if ($missing !== []) {
            $this->activationService->deactivate($bulletinType);

            return back()->with('error', 'flash.bulletinCannotActivate');
        }

        $this->activationService->activate($bulletinType);

        return back()->with('success', 'flash.bulletinTurnedOn');
    }

    public function destroy(BulletinType $bulletinType): RedirectResponse
    {
        $bulletinType->delete();

        return to_route('editor.bulletin-types.index')->with('success', 'flash.bulletinDeleted');
    }

    private function enrichValidatedData(Request $request, array $data): array
    {
        $metadata = $data['metadata'] ?? [];
        $times = collect($data['default_run_times'] ?? [])
            ->push($data['default_run_time'] ?? null)
            ->push($data['default_schedule_time'] ?? null)
            ->filter()
            ->map(fn ($time) => substr((string) $time, 0, 5))
            ->unique()
            ->sort()
            ->values()
            ->all();

        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['coverage_mode'] = $data['coverage_mode'] ?? 'previous_period';
        $data['prompt_language'] = $data['prompt_language'] ?? 'es';
        $data['default_run_frequency'] = $data['default_run_frequency'] ?? 'daily';
        $data['default_run_time'] = $times[0] ?? $data['default_run_time'] ?? $data['default_schedule_time'] ?? null;
        $data['default_schedule_time'] = $data['default_run_time'];
        $data['default_run_days'] = in_array($data['default_run_frequency'], ['selected_days', 'weekly', 'custom'], true)
            ? array_values($data['default_run_days'] ?? [])
            : null;
        $data['default_schedule_is_active'] = $request->boolean('default_schedule_is_active');
        $data['default_auto_run_pipeline'] = $request->boolean('default_auto_run_pipeline');
        $data['default_auto_generate_ai_response'] = $request->boolean('default_auto_generate_ai_response');
        $data['default_auto_create_script'] = $request->boolean('default_auto_create_script', true);
        $data['default_auto_generate_metadata'] = $request->boolean('default_auto_generate_metadata', true);
        $data['default_auto_extract_sources'] = $request->boolean('default_auto_extract_sources', true);
        $data['output_mode'] = $data['output_mode'] ?? 'plain_final_script';
        $data['include_future_agenda'] = $request->boolean('include_future_agenda');
        $data['include_historical_context'] = $request->boolean('include_historical_context');
        $data['metadata'] = [
            ...$metadata,
            'schedule_times' => $times,
            'month_day' => $data['default_run_frequency'] === 'monthly' ? (int) ($data['default_month_day'] ?? 1) : null,
            'schedule_date' => $data['default_run_frequency'] === 'once' ? ($data['default_schedule_date'] ?? null) : null,
        ];

        unset($data['default_run_times'], $data['default_month_day'], $data['default_schedule_date']);

        if (isset($data['min_news_items'], $data['max_news_items']) && $data['min_news_items'] > $data['max_news_items']) {
            [$data['min_news_items'], $data['max_news_items']] = [$data['max_news_items'], $data['min_news_items']];
        }

        return $data;
    }

    private function validated(Request $request, ?BulletinType $bulletinType = null): array
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('bulletin_types', 'slug')->ignore($bulletinType?->id)],
            'description' => ['nullable', 'string'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'news_category_id' => ['nullable', 'exists:news_categories,id'],
            'language_id' => ['nullable', 'exists:languages,id'],
            'default_prompt_profile_id' => ['nullable', 'exists:prompt_profiles,id'],
            'preferred_ai_provider_id' => ['nullable', 'exists:ai_providers,id'],
            'edition_type' => ['nullable', 'string', 'max:50'],
            'target_duration_seconds' => ['nullable', 'integer', 'min:15', 'max:3600'],
            'default_schedule_time' => ['nullable', 'date_format:H:i'],
            'default_timezone' => ['nullable', 'string', 'max:100', 'timezone'],
            'default_run_frequency' => ['nullable', Rule::in(['once','daily','weekdays','weekends','selected_days','weekly','monthly','custom'])],
            'default_run_time' => ['nullable', 'date_format:H:i'],
            'default_run_times' => ['nullable', 'array'],
            'default_run_times.*' => ['nullable', 'date_format:H:i'],
            'default_run_days' => ['nullable', 'array'],
            'default_run_days.*' => [Rule::in(['monday','tuesday','wednesday','thursday','friday','saturday','sunday'])],
            'default_month_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'default_schedule_date' => ['nullable', 'date'],
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
            'output_mode' => ['nullable', Rule::in(['plain_final_script', 'structured_script', 'plain_script', 'final_plain_script', 'final_script'])],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            $frequency = (string) $request->input('default_run_frequency', 'daily');
            $times = collect($request->input('default_run_times', []))
                ->push($request->input('default_run_time'))
                ->push($request->input('default_schedule_time'))
                ->filter()
                ->values();
            $schedulingEnabled = $request->boolean('is_active') || $request->boolean('default_schedule_is_active') || $times->isNotEmpty();

            if (! $schedulingEnabled) {
                return;
            }

            if (! $request->filled('default_run_frequency')) {
                $validator->errors()->add('default_run_frequency', 'validation.bulletinScheduleFrequencyRequired');
            }

            if ($times->isEmpty()) {
                $validator->errors()->add('default_run_times', 'validation.bulletinScheduleTimesRequired');
            }

            if (in_array($frequency, ['selected_days', 'weekly', 'custom'], true) && count($request->input('default_run_days', [])) === 0) {
                $validator->errors()->add('default_run_days', 'validation.bulletinScheduleDaysRequired');
            }

            if ($frequency === 'monthly' && ! $request->filled('default_month_day')) {
                $validator->errors()->add('default_month_day', 'validation.bulletinScheduleMonthDayRequired');
            }

            if ($frequency === 'once' && ! $request->filled('default_schedule_date')) {
                $validator->errors()->add('default_schedule_date', 'validation.bulletinScheduleDateRequired');
            }

            if ($frequency === 'custom' && ($times->isEmpty() || count($request->input('default_run_days', [])) === 0)) {
                $validator->errors()->add('default_run_frequency', 'validation.bulletinCustomScheduleRequired');
            }
        });

        return $validator->validate();
    }

    private function options(): array
    {
        return [
            'locations' => Location::query()->orderBy('name')->get(['id', 'name']),
            'categories' => NewsCategory::query()->orderBy('name')->get(['id', 'name']),
            'languages' => Language::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'promptProfiles' => PromptProfile::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get(['id', 'name']),
            'scriptProviders' => $this->scriptProviderQuery()
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get(['id','name','slug','provider_category','default_model','supports_grounding','capabilities']),
            'editionTypes' => ['morning', 'afternoon', 'night', 'special'],
            'frequencyTypes' => ['once', 'daily', 'weekdays', 'weekends', 'selected_days', 'weekly', 'monthly', 'custom'],
            'weekdays' => ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'],
            'coverageModes' => ['previous_period', 'today_so_far', 'yesterday', 'last_24_hours', 'next_24_hours', 'custom', 'none'],
            'promptLanguages' => ['es', 'en', 'fr'],
            'outputModes' => ['plain_final_script', 'structured_script'],
        ];
    }

    private function scriptProviderQuery()
    {
        return AiProvider::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->where('is_testing', false)->orWhereNull('is_testing');
            })
            ->where(function ($q): void {
                $q->whereIn('provider_category', ['text', 'grounded_text', 'local'])
                    ->orWhereJsonContains('capabilities', 'script_generation')
                    ->orWhereJsonContains('capabilities', 'news_grounding')
                    ->orWhereJsonContains('capabilities', 'text_generation');
            });
    }

    private function scheduleConfig(BulletinType $bulletinType): array
    {
        $schedules = $this->configuredSchedules($bulletinType);
        $primary = $bulletinType->primarySchedule ?: $schedules->first();
        $metadataTimes = collect(data_get($bulletinType->metadata, 'schedule_times', []))
            ->map(fn ($time) => substr((string) $time, 0, 5))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return [
            'frequency' => $bulletinType->default_run_frequency ?: $primary?->run_frequency ?: 'daily',
            'times' => ($metadataTimes->isNotEmpty() ? $metadataTimes : $schedules
                ->map(fn (EditorialSchedule $schedule) => substr((string) ($schedule->run_time ?: $schedule->scheduled_time), 0, 5))
                ->filter()
                ->unique()
                ->sort()
                ->values()),
            'days' => array_values($bulletinType->default_run_days ?? $primary?->run_days ?? []),
            'timezone' => $bulletinType->default_timezone ?: $primary?->timezone ?: config('app.timezone'),
            'month_day' => (int) data_get($bulletinType->metadata, 'month_day', data_get($primary?->metadata, 'month_day', 1)),
            'schedule_date' => data_get($bulletinType->metadata, 'schedule_date', optional($primary?->scheduled_date)?->toDateString()),
        ];
    }

    private function scheduleSummary(BulletinType $bulletinType): array
    {
        $schedules = $this->configuredSchedules($bulletinType)->sortBy(fn (EditorialSchedule $schedule) => ($schedule->run_time ?: $schedule->scheduled_time ?: '99:99'));
        $active = $schedules->filter(fn (EditorialSchedule $schedule) => $schedule->is_active);
        $next = $active->pluck('next_run_at')->filter()->sort()->first();
        $first = $schedules->first();

        return [
            'total' => $schedules->count(),
            'active' => $active->count(),
            'times' => $schedules
                ->map(fn (EditorialSchedule $schedule) => substr((string) ($schedule->run_time ?: $schedule->scheduled_time), 0, 5))
                ->filter()
                ->unique()
                ->values(),
            'active_times' => $active
                ->map(fn (EditorialSchedule $schedule) => substr((string) ($schedule->run_time ?: $schedule->scheduled_time), 0, 5))
                ->filter()
                ->unique()
                ->values(),
            'frequency' => $first?->run_frequency ?: $bulletinType->default_run_frequency,
            'days' => array_values($first?->run_days ?? $bulletinType->default_run_days ?? []),
            'month_day' => data_get($first?->metadata, 'month_day', data_get($bulletinType->metadata, 'month_day')),
            'next_run' => optional($next)?->toIso8601String(),
        ];
    }

    private function configuredSchedules(BulletinType $bulletinType): \Illuminate\Support\Collection
    {
        return $bulletinType->schedules
            ->reject(fn (EditorialSchedule $schedule) => (bool) data_get($schedule->metadata, 'obsolete_from_bulletin_config'))
            ->values();
    }

    private function presentScheduleRow(EditorialSchedule $schedule): array
    {
        return [
            'id' => $schedule->id,
            'is_primary' => (bool) $schedule->is_primary,
            'is_active' => (bool) $schedule->is_active,
            'frequency' => $schedule->run_frequency ?: $schedule->frequency_type,
            'time' => substr((string) ($schedule->run_time ?: $schedule->scheduled_time), 0, 5),
            'timezone' => $schedule->timezone,
            'run_days' => array_values($schedule->run_days ?? []),
            'month_day' => data_get($schedule->metadata, 'month_day'),
            'slot' => data_get($schedule->metadata, 'inferred_slot'),
            'scheduled_date' => optional($schedule->scheduled_date)?->toDateString(),
            'next_run_at' => optional($schedule->next_run_at)?->toIso8601String(),
            'last_run_at' => optional($schedule->last_run_at)?->toIso8601String(),
            'auto_generate_ai_response' => (bool) $schedule->auto_generate_ai_response,
            'auto_run_pipeline' => (bool) $schedule->auto_run_pipeline,
            'run_now_url' => $this->safeRoute('editor.editorial-schedules.run-now', $schedule),
            'toggle_url' => $this->safeRoute('editor.automation.schedules.toggle', $schedule),
        ];
    }

    private function presentBulletinIndexRow(BulletinType $bulletinType): array
    {
        $data = $bulletinType->toArray();
        $schedule = $this->activationService->runnableSchedule($bulletinType) ?? $bulletinType->primarySchedule;
        $missing = $this->activationService->missingConfiguration($bulletinType);
        $isRunnable = $this->activationService->isRunnable($bulletinType);
        $latestExecution = EditorialScheduleRun::query()
            ->whereHas('schedule', fn ($query) => $query->where('bulletin_type_id', $bulletinType->id))
            ->latest('scheduled_for')
            ->first(['id', 'status', 'scheduled_for', 'script_id', 'bulletin_prompt_run_id']);
        $latestScript = Script::query()
            ->whereHas('bulletinPromptRun', fn ($query) => $query->where('bulletin_type_id', $bulletinType->id))
            ->latest()
            ->first(['id', 'title', 'review_status', 'production_status', 'created_at']);

        return [
            ...$data,
            'latest_execution' => $latestExecution ? [
                'id' => $latestExecution->id,
                'status' => $latestExecution->status,
                'scheduled_for' => optional($latestExecution->scheduled_for)?->toIso8601String(),
                'url' => $this->safeRoute('editor.editorial-schedule-runs.show', $latestExecution),
            ] : null,
            'latest_script' => $latestScript ? [
                'id' => $latestScript->id,
                'title' => $latestScript->title,
                'review_status' => $latestScript->review_status,
                'production_status' => $latestScript->production_status,
                'created_at' => optional($latestScript->created_at)?->toIso8601String(),
                'url' => $this->safeRoute('editor.scripts.show', $latestScript),
            ] : null,
            'health' => $this->bulletinHealth($bulletinType),
            'missing_configuration' => $missing,
            'is_on' => $isRunnable,
            'is_runnable' => $isRunnable,
            'can_activate' => $missing === [],
            'schedule_summary' => $this->scheduleSummary($bulletinType),
            'urls' => [
                'show' => $this->safeRoute('editor.bulletin-types.show', $bulletinType),
                'edit' => $this->safeRoute('editor.bulletin-types.edit', $bulletinType),
                'run_now' => $schedule ? $this->safeRoute('editor.editorial-schedules.run-now', $schedule) : null,
                'toggle_active' => $this->safeRoute('editor.bulletin-types.toggle-active', $bulletinType),
                'toggle_schedule' => $schedule ? $this->safeRoute('editor.automation.schedules.toggle', $schedule) : null,
                'executions' => $this->safeRoute('editor.editorial-schedule-runs.index', ['schedule_id' => $schedule?->id]),
                'scripts' => $this->safeRoute('editor.scripts.index', ['bulletin_type_id' => $bulletinType->id]),
            ],
        ];
    }

    private function recentExecutions(BulletinType $bulletinType): array
    {
        return EditorialScheduleRun::query()
            ->with(['bulletinPromptRun:id,status,generated_prompt,ai_response_text,script_id', 'script:id,title,status'])
            ->whereHas('schedule', fn ($query) => $query->where('bulletin_type_id', $bulletinType->id))
            ->latest('scheduled_for')
            ->limit(8)
            ->get()
            ->map(fn (EditorialScheduleRun $run) => [
                'id' => $run->id,
                'scheduled_for' => optional($run->scheduled_for)?->toIso8601String(),
                'status' => $run->status,
                'prompt_generated' => filled($run->generated_prompt) || filled($run->bulletinPromptRun?->generated_prompt),
                'ai_response_received' => filled($run->ai_response_text) || filled($run->bulletinPromptRun?->ai_response_text),
                'script_created' => filled($run->script_id) || filled($run->bulletinPromptRun?->script_id),
                'error_message' => $run->error_message,
                'url' => $this->safeRoute('editor.editorial-schedule-runs.show', $run),
                'script_url' => ($run->script_id || $run->bulletinPromptRun?->script_id) ? $this->safeRoute('editor.scripts.show', $run->script_id ?: $run->bulletinPromptRun?->script_id) : null,
            ])
            ->all();
    }

    private function recentScripts(BulletinType $bulletinType): array
    {
        return Script::query()
            ->whereHas('bulletinPromptRun', fn ($query) => $query->where('bulletin_type_id', $bulletinType->id))
            ->latest()
            ->limit(8)
            ->get(['id', 'title', 'status', 'review_status', 'production_status', 'created_at'])
            ->map(fn (Script $script) => [
                'id' => $script->id,
                'title' => $script->title,
                'status' => $script->status,
                'review_status' => $script->review_status,
                'production_status' => $script->production_status,
                'created_at' => optional($script->created_at)?->toIso8601String(),
                'url' => $this->safeRoute('editor.scripts.show', $script),
                'review_url' => $this->safeRoute('editor.scripts.review', $script),
            ])
            ->all();
    }

    private function promptPreview(BulletinType $bulletinType): array
    {
        $schedule = $bulletinType->primarySchedule;
        $scheduledFor = $schedule?->next_run_at ?: now();
        $previewRun = new BulletinPromptRun([
            'bulletin_type_id' => $bulletinType->id,
            'prompt_profile_id' => $bulletinType->default_prompt_profile_id,
            'scheduled_for' => $scheduledFor,
            'title' => $bulletinType->name.' preview',
        ]);
        $previewRun->setRelation('bulletinType', $bulletinType);
        if ($bulletinType->promptProfile) {
            $previewRun->setRelation('promptProfile', $bulletinType->promptProfile);
        }

        try {
            $text = $this->promptGenerator->generate($previewRun);
        } catch (\Throwable $exception) {
            $text = '';
        }

        return [
            'text' => $text,
            'scheduled_for' => optional($scheduledFor)?->toIso8601String(),
            'location' => $bulletinType->location?->name,
            'category' => $bulletinType->newsCategory?->name,
            'duration' => $bulletinType->target_duration_seconds,
            'purpose' => $bulletinType->description,
        ];
    }

    private function bulletinHealth(BulletinType $bulletinType): array
    {
        $schedule = $bulletinType->primarySchedule;
        $missing = $this->activationService->missingConfiguration($bulletinType);
        $warnings = $missing;
        $isRunnable = $this->activationService->isRunnable($bulletinType);

        if (! $bulletinType->is_active) {
            $warnings[] = 'bulletinTypes.health.bulletinOff';
        } elseif ($schedule && ! $schedule->is_active) {
            $warnings[] = 'bulletinTypes.health.scheduleOff';
        }

        return [
            'status' => $isRunnable ? 'ready' : 'attention',
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    private function safeRoute(string $name, mixed $parameters = []): ?string
    {
        return Route::has($name) ? route($name, $parameters) : null;
    }
}
