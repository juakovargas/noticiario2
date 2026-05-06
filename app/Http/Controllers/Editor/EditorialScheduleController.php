<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\AiPromptTemplate;
use App\Models\EditorialSchedule;
use App\Models\EditorialTemplate;
use App\Services\Scheduling\EditorialScheduleRunner;
use App\Models\Language;
use App\Models\Location;
use App\Models\NewsCategory;
use App\Services\Editor\BulletinTypeActivationService;
use App\Services\EditorialScheduling\EditorialScheduleRunService;
use App\Services\Pipelines\BulletinPromptRunPipeline;
use App\Support\GeneratesUniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EditorialScheduleController extends Controller
{
    use GeneratesUniqueSlug;

    public function __construct(
        private readonly EditorialScheduleRunService $runService,
        private readonly EditorialScheduleRunner $scheduleRunner,
        private readonly BulletinPromptRunPipeline $pipelineService,
        private readonly BulletinTypeActivationService $activationService,
    )
    {
    }

    public function index(Request $request): Response
    {
        $query = EditorialSchedule::query()->with(['location:id,name', 'newsCategory:id,name', 'language:id,name,code', 'bulletinType:id,name']);

        if ($request->filled('search')) {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%')->orWhere('slug', 'like', '%'.$request->string('search').'%'));
        }

        foreach (['location_id', 'news_category_id', 'language_id', 'frequency_type', 'edition_type', 'is_active'] as $filter) {
            if ($request->filled($filter) || $request->input($filter) === '0') {
                $query->where($filter, $request->input($filter));
            }
        }

        return Inertia::render('Editor/EditorialSchedules/Index', [
            'schedules' => $query->latest()->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'location_id', 'news_category_id', 'language_id', 'frequency_type', 'edition_type', 'is_active']),
            ...$this->formOptions(),
            'frequencyTypes' => ['daily', 'weekdays', 'weekly', 'once', 'custom'],
            'editionTypes' => ['morning', 'afternoon', 'night', 'special'],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Editor/EditorialSchedules/Create', [
            ...$this->formOptions(),
            'frequencyTypes' => ['daily', 'weekdays', 'weekly', 'once', 'custom'],
            'editionTypes' => ['morning', 'afternoon', 'night', 'special'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $data['slug'] ?: $this->uniqueSlug(EditorialSchedule::class, $data['name']);
        $data['weekdays'] = $data['weekdays'] ?? null;
        $data['run_days'] = $data['run_days'] ?? $data['weekdays'] ?? null;
        $data['run_frequency'] = $data['run_frequency'] ?? $data['frequency_type'];
        $data['run_time'] = $data['run_time'] ?? $data['scheduled_time'];
        if (empty($data['next_run_at'])) {
            $draft = new EditorialSchedule($data);
            $data['next_run_at'] = $this->scheduleRunner->calculateNextRunAt($draft)?->utc();
        }

        $schedule = EditorialSchedule::query()->create($data);

        return to_route('editor.editorial-schedules.show', $schedule)->with('success', 'Editorial schedule created successfully.');
    }

    public function show(EditorialSchedule $editorialSchedule): Response
    {
        $editorialSchedule->load(['location:id,name', 'newsCategory:id,name', 'language:id,name,code', 'bulletinType:id,name', 'runs.edition:id,title', 'runs.script:id,title']);

        return Inertia::render('Editor/EditorialSchedules/Show', [
            'schedule' => $editorialSchedule,
            'recentRuns' => $editorialSchedule->runs()->with(['edition:id,title', 'script:id,title'])->latest()->limit(10)->get(),
        ]);
    }

    public function edit(EditorialSchedule $editorialSchedule): Response
    {
        return Inertia::render('Editor/EditorialSchedules/Edit', [
            'schedule' => $editorialSchedule,
            ...$this->formOptions(),
            'frequencyTypes' => ['daily', 'weekdays', 'weekly', 'once', 'custom'],
            'editionTypes' => ['morning', 'afternoon', 'night', 'special'],
        ]);
    }

    public function update(Request $request, EditorialSchedule $editorialSchedule): RedirectResponse
    {
        $data = $this->validated($request, $editorialSchedule);
        $data['slug'] = $data['slug'] ?: $this->uniqueSlug(EditorialSchedule::class, $data['name'], $editorialSchedule->id);
        $data['weekdays'] = $data['weekdays'] ?? null;
        $data['run_days'] = $data['run_days'] ?? $data['weekdays'] ?? null;
        $data['run_frequency'] = $data['run_frequency'] ?? $data['frequency_type'];
        $data['run_time'] = $data['run_time'] ?? $data['scheduled_time'];
        if (empty($data['next_run_at'])) {
            $draft = new EditorialSchedule($data);
            $data['next_run_at'] = $this->scheduleRunner->calculateNextRunAt($draft)?->utc();
        }
        $editorialSchedule->update($data);

        return to_route('editor.editorial-schedules.show', $editorialSchedule)->with('success', 'Editorial schedule updated successfully.');
    }

    public function destroy(EditorialSchedule $editorialSchedule): RedirectResponse
    {
        $editorialSchedule->delete();

        return to_route('editor.editorial-schedules.index')->with('success', 'Editorial schedule deleted successfully.');
    }

    public function createRun(EditorialSchedule $editorialSchedule): RedirectResponse
    {
        $run = $this->runService->createRunFromSchedule($editorialSchedule->load(['location', 'newsCategory', 'language']));

        return to_route('editor.editorial-schedule-runs.show', $run)->with('success', 'Editorial run created successfully.');
    }


    public function runNow(EditorialSchedule $editorialSchedule): RedirectResponse
    {
        $editorialSchedule->load(['bulletinType.preferredAiProvider', 'bulletinType.location', 'bulletinType.newsCategory', 'bulletinType.language', 'bulletinType.schedules']);

        if (! $editorialSchedule->is_active || ! $editorialSchedule->bulletinType?->is_active) {
            return back()->with('error', 'flash.bulletinCannotRunOff');
        }

        if (! $editorialSchedule->bulletinType || ! $this->activationService->isComplete($editorialSchedule->bulletinType)) {
            return back()->with('error', 'flash.bulletinCannotRunIncomplete');
        }

        $run = $this->scheduleRunner->createRunForSchedule($editorialSchedule, now()->utc()->startOfMinute(), ['generate_prompts' => true]);

        if (! $run->wasRecentlyCreated) {
            return to_route('editor.editorial-schedule-runs.show', $run)->with('warning', __('Execution already exists for this scheduled time.'));
        }

        $promptRun = $run->bulletinPromptRun;
        if ($promptRun && $editorialSchedule->auto_generate_ai_response && $editorialSchedule->auto_run_pipeline) {
            $summary = $this->pipelineService->run($promptRun->refresh(), request()->user(), [
                'allow_ai_call' => true,
                'generate_metadata' => (bool) $editorialSchedule->auto_generate_metadata,
                'extract_sources' => (bool) $editorialSchedule->auto_extract_sources,
            ]);

            if ($summary['success']) {
                $editorialSchedule->forceFill(['last_success_at' => now(), 'last_error_message' => null])->save();

                if ($summary['script_id']) {
                    return to_route('editor.scripts.show', $summary['script_id'])->with('success', __('Pipeline completed. Script created.'));
                }

                return to_route('editor.editorial-schedule-runs.show', $run->refresh())->with('success', __('Pipeline completed.'));
            }

            $editorialSchedule->forceFill([
                'last_failure_at' => now(),
                'last_error_message' => ($summary['failed_step'] ?? 'unknown').' - '.($summary['message'] ?? ''),
            ])->save();

            return to_route('editor.editorial-schedule-runs.show', $run->refresh())
                ->with('error', __('Pipeline failed').': '.($summary['failed_step'] ?? 'unknown').' - '.($summary['message'] ?? ''));
        }

        return to_route('editor.editorial-schedule-runs.show', $run)
            ->with('success', $editorialSchedule->auto_generate_ai_response ? __('Editorial run created successfully.') : __('AI manual approval required.'));
    }

    public function recalculateNextRun(EditorialSchedule $editorialSchedule): RedirectResponse
    {
        $editorialSchedule->update([
            'next_run_at' => $this->scheduleRunner->calculateNextRunAt($editorialSchedule),
        ]);

        return back()->with('success', 'Next run recalculated successfully.');
    }

    private function formOptions(): array
    {
        return [
            'locations' => Location::query()->orderBy('name')->get(['id', 'name']),
            'categories' => NewsCategory::query()->orderBy('name')->get(['id', 'name']),
            'languages' => Language::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'editorialTemplates' => class_exists(EditorialTemplate::class) ? EditorialTemplate::query()->orderBy('name')->get(['id', 'name']) : [],
            'aiPromptTemplates' => class_exists(AiPromptTemplate::class) ? AiPromptTemplate::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']) : [],
            'bulletinTypes' => \App\Models\BulletinType::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ];
    }

    private function validated(Request $request, ?EditorialSchedule $schedule = null): array
    {
        $rule = Rule::unique('editorial_schedules', 'slug');
        if ($schedule) {
            $rule = $rule->ignore($schedule->id);
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', $rule],
            'description' => ['nullable', 'string'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'news_category_id' => ['nullable', 'exists:news_categories,id'],
            'language_id' => ['nullable', 'exists:languages,id'],
            'bulletin_type_id' => ['nullable', 'exists:bulletin_types,id'],
            'edition_type' => ['required', 'string', 'max:50'],
            'frequency_type' => ['required', 'string', 'max:50'],
            'run_frequency' => ['nullable', 'string', 'max:50'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            'run_time' => ['nullable', 'date_format:H:i'],
            'scheduled_date' => ['nullable', 'date'],
            'weekdays' => ['nullable', 'array'],
            'run_days' => ['nullable', 'array'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'next_run_at' => ['nullable', 'date'],
            'target_duration_seconds' => ['nullable', 'integer', 'min:15', 'max:3600'],
            'tone' => ['nullable', 'string', 'max:100'],
            'manual_ai_mode' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'auto_create_prompt_run' => ['sometimes', 'boolean'],
            'auto_generate_prompt' => ['sometimes', 'boolean'],
            'auto_generate_ai_response' => ['sometimes', 'boolean'],
            'editorial_instructions' => ['nullable', 'string'],
            'output_instructions' => ['nullable', 'string'],
        ];

        if (class_exists(EditorialTemplate::class)) {
            $rules['editorial_template_id'] = ['nullable', 'exists:editorial_templates,id'];
        }

        if (class_exists(AiPromptTemplate::class)) {
            $rules['ai_prompt_template_id'] = ['nullable', 'exists:ai_prompt_templates,id'];
        }

        return $request->validate($rules);
    }
}
