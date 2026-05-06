<?php

namespace App\Services\Editor;

use App\Models\AiProvider;
use App\Models\BulletinPromptRun;
use App\Models\BulletinType;
use App\Models\EditorialSchedule;
use App\Models\EditorialScheduleRun;
use App\Models\Script;
use App\Models\SourceReference;
use App\Services\Maps\BulletinMapService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class EditorDashboardOverviewService
{
    public function __construct(
        private readonly BulletinMapService $mapService,
        private readonly BulletinTypeActivationService $activationService,
    ) {}

    public function build(): array
    {
        $now = now();

        $schedules = EditorialSchedule::query()
            ->with([
                'bulletinType:id,name,is_active,location_id,news_category_id,language_id,target_duration_seconds,preferred_ai_provider_id',
                'bulletinType.preferredAiProvider:id,name,default_model,supports_grounding,is_active,provider_category,rate_limited_until',
                'location:id,name',
                'newsCategory:id,name',
                'language:id,name,code',
                'runs' => fn ($q) => $q->latest('scheduled_for')->limit(1),
            ])
            ->get();

        $bulletins = BulletinType::query()
            ->with([
                'preferredAiProvider:id,name,default_model,supports_grounding,is_active,provider_category,rate_limited_until',
                'primarySchedule:id,bulletin_type_id,is_primary,is_active,run_frequency,run_time,scheduled_time,scheduled_date,timezone,next_run_at,last_run_at,auto_generate_ai_response,auto_run_pipeline,metadata',
                'schedules:id,bulletin_type_id,is_primary,is_active,run_frequency,run_time,scheduled_time,scheduled_date,timezone,next_run_at,last_run_at,auto_generate_ai_response,auto_run_pipeline,metadata',
                'location:id,name',
                'newsCategory:id,name',
                'language:id,name,code',
            ])
            ->get();
        $summary = $this->buildSummary($schedules, $now);

        return [
            'headerActions' => [
                [
                    'key' => 'automation',
                    'label' => 'dashboard.action.viewAutomation',
                    'href' => $this->safeRoute('editor.automation.index'),
                ],
                [
                    'key' => 'createBulletin',
                    'label' => 'dashboard.action.createBulletin',
                    'href' => $this->safeRoute('editor.bulletin-types.create'),
                ],
                [
                    'key' => 'fullMap',
                    'label' => 'dashboard.action.viewFullMap',
                    'href' => $this->safeRoute('editor.world-map.index'),
                ],
            ],
            'summaryCards' => $this->summaryCardsFromSummary($summary),
            'stats' => $this->legacyStats($summary, $now),
            'mapOverview' => [
                'markers' => $this->mapService->getEditorMapData(),
                'mapRoute' => $this->safeRoute('editor.world-map.index'),
                'locations' => $this->locationOverview($bulletins),
            ],
            'nextScheduledRuns' => $this->nextScheduledRuns($schedules, $now)->values(),
            'todayTimelineItems' => $this->todayTimeline($schedules, $now)->values(),
            'scheduledBulletins' => $this->scheduledBulletins($schedules),
            'actionableQueue' => $this->buildActionableQueue($schedules, $bulletins, $now)->values(),
            'aiEngines' => $this->buildAiEngines($now, $bulletins)->values(),
            'coverageByLocationTopic' => $this->buildCoverageOverview($bulletins),
            'latestExecutions' => $this->buildLatestExecutions()->values(),
            'scriptsNeedingReview' => $this->scriptsNeedingReview(),
            'readyToApprove' => $this->readyToApproveScripts(),
        ];
    }

    private function summaryCards(Collection $schedules, Carbon $now): array
    {
        return $this->summaryCardsFromSummary($this->buildSummary($schedules, $now));
    }

    private function summaryCardsFromSummary(array $summary): array
    {
        return [
            ['key' => 'next24h', 'label' => 'dashboard.summary.next24h', 'value' => $summary['next24h']],
            ['key' => 'overdue', 'label' => 'dashboard.summary.overdue', 'value' => $summary['overdue']],
            ['key' => 'failed', 'label' => 'dashboard.summary.failedToday', 'value' => $summary['failedToday']],
            ['key' => 'pendingReview', 'label' => 'dashboard.summary.pendingReview', 'value' => $summary['pendingReview']],
            ['key' => 'sourcesPending', 'label' => 'dashboard.summary.sourcesPending', 'value' => $summary['sourcesPending']],
            ['key' => 'readyProduction', 'label' => 'dashboard.summary.readyProduction', 'value' => $summary['readyProduction']],
        ];
    }

    private function legacyStats(array $summary, Carbon $now): array
    {
        return [
            'scriptsPendingReview' => $summary['pendingReview'],
            'scriptsNeedingSources' => $summary['sourcesPending'],
            'scriptsApprovedToday' => Script::query()
                ->where('status', '!=', 'archived')
                ->where('review_status', 'approved')
                ->whereDate('updated_at', $now->toDateString())
                ->count(),
        ];
    }

    private function buildSummary(Collection $schedules, Carbon $now): array
    {
        return [
            'next24h' => $schedules
                ->filter(fn ($schedule) => $this->isEligibleForScheduling($schedule) && $schedule->next_run_at && $schedule->next_run_at->betweenIncluded($now, $now->copy()->addDay()))
                ->count(),
            'overdue' => $schedules
                ->filter(fn ($schedule) => $this->isEligibleForScheduling($schedule) && $schedule->next_run_at && $schedule->next_run_at->isPast())
                ->count(),
            'failedToday' => EditorialScheduleRun::query()
                ->whereHas('schedule', fn ($query) => $query
                    ->where('is_active', true)
                    ->whereHas('bulletinType', fn ($bulletinQuery) => $bulletinQuery->where('is_active', true)))
                ->whereDate('scheduled_for', $now->toDateString())
                ->where('status', 'failed')
                ->count(),
            'pendingReview' => Script::query()
                ->where('review_status', 'pending')
                ->where('status', '!=', 'archived')
                ->count(),
            'sourcesPending' => SourceReference::query()
                ->withoutArchived()
                ->where('verification_status', 'pending')
                ->count(),
            'readyProduction' => Script::query()
                ->where('status', '!=', 'archived')
                ->where('production_status', 'ready_for_production')
                ->count(),
        ];
    }

    private function nextScheduledRuns(Collection $schedules, Carbon $now): Collection
    {
        return $schedules
            ->filter(fn ($schedule) => $this->isEligibleForScheduling($schedule) && $schedule->next_run_at && $schedule->next_run_at->betweenIncluded($now, $now->copy()->addDay()))
            ->sortBy('next_run_at')
            ->take(12)
            ->map(fn ($schedule) => $this->scheduleRunRow($schedule));
    }

    private function todayTimeline(Collection $schedules, Carbon $now): Collection
    {
        $start = $now->copy()->startOfDay();
        $end = $now->copy()->endOfDay();

        $runs = EditorialScheduleRun::query()
            ->with([
                'schedule.bulletinType.preferredAiProvider:id,name,default_model',
                'schedule.bulletinType:id,name,is_active,preferred_ai_provider_id',
                'bulletinPromptRun:id,title,status,generated_prompt,script_id',
                'script:id,title,production_status',
                'sourceReferences:id,editorial_schedule_run_id,verification_status',
            ])
            ->whereBetween('scheduled_for', [$start, $end])
            ->whereHas('schedule', fn ($query) => $query
                ->where('is_active', true)
                ->whereHas('bulletinType', fn ($bulletinQuery) => $bulletinQuery->where('is_active', true)))
            ->orderBy('scheduled_for')
            ->get()
            ->map(fn (EditorialScheduleRun $run) => $this->executionRow($run));

        $existing = $runs
            ->map(fn (array $run) => ($run['schedule_id'] ?? '').'|'.($run['scheduled_for'] ?? ''))
            ->filter()
            ->flip();

        $scheduled = $schedules
            ->filter(fn ($schedule) => $this->isEligibleForScheduling($schedule) && $schedule->next_run_at && $schedule->next_run_at->betweenIncluded($start, $end))
            ->reject(function ($schedule) use ($existing) {
                $nextRun = optional($schedule->next_run_at)->toIso8601String();

                return $existing->has($schedule->id.'|'.$nextRun);
            })
            ->sortBy('next_run_at')
            ->map(fn ($schedule) => [
                ...$this->scheduleRunRow($schedule),
                'type' => 'schedule',
                'status' => 'scheduled',
            ]);

        return $runs
            ->concat($scheduled)
            ->sortBy('scheduled_for')
            ->values();
    }

    private function scheduledBulletins(Collection $schedules): array
    {
        return $schedules
            ->filter(fn ($schedule) => $this->isEligibleForScheduling($schedule))
            ->groupBy('bulletin_type_id')
            ->map(function (Collection $bulletinSchedules) {
                $sortedSchedules = $bulletinSchedules->sortBy(fn ($schedule) => $schedule->next_run_at ?: $schedule->run_time ?: $schedule->scheduled_time);
                $schedule = $sortedSchedules->first();
                $lastRun = $sortedSchedules
                    ->flatMap(fn ($item) => $item->runs)
                    ->sortByDesc('scheduled_for')
                    ->first();
                $provider = $schedule->bulletinType?->preferredAiProvider;
                $missing = $this->missingConfiguration($schedule);
                $nextSchedule = $sortedSchedules->first(fn ($item) => filled($item->next_run_at)) ?? $schedule;
                $activeSchedules = $sortedSchedules->filter(fn ($item) => $item->is_active);

                return [
                    'row_id' => 'bulletin-'.$schedule->bulletin_type_id,
                    'id' => $schedule->id,
                    'schedule_id' => $nextSchedule->id,
                    'bulletin_id' => $schedule->bulletinType?->id,
                    'bulletin' => $schedule->bulletinType?->name ?? $schedule->name ?? null,
                    'bulletin_url' => $schedule->bulletinType ? $this->safeRoute('editor.bulletin-types.show', $schedule->bulletinType) : '#',
                    'location' => $schedule->location?->name,
                    'category' => $schedule->newsCategory?->name,
                    'provider' => $provider?->name,
                    'model' => $provider?->default_model,
                    'grounded' => (bool) $provider?->supports_grounding,
                    'frequency' => $schedule->run_frequency,
                    'time' => $schedule->run_time ?: $schedule->scheduled_time,
                    'timezone' => $schedule->timezone,
                    'next_run' => optional($nextSchedule->next_run_at)?->toIso8601String(),
                    'today_times' => $activeSchedules
                        ->filter(fn ($item) => $item->next_run_at && $item->next_run_at->isToday())
                        ->map(fn ($item) => substr((string) ($item->run_time ?: $item->scheduled_time), 0, 5))
                        ->values(),
                    'last_run' => optional($schedule->last_run_at ?: $lastRun?->scheduled_for)?->toIso8601String(),
                    'last_result' => $lastRun?->status,
                    'is_on' => $this->isOperational($schedule),
                    'is_incomplete' => $missing !== [],
                    'missing_configuration' => $missing,
                    'ai_manual_approval_required' => ! (bool) $schedule->auto_generate_ai_response,
                    'pipeline' => $this->schedulePipeline($schedule, $lastRun),
                    'schedule_summary' => $this->scheduleSummary($sortedSchedules),
                    'view_url' => $schedule->bulletinType ? $this->safeRoute('editor.bulletin-types.show', $schedule->bulletinType) : '#',
                    'schedule_url' => $this->safeRoute('editor.editorial-schedules.show', $nextSchedule),
                    'runs_url' => $this->safeRoute('editor.editorial-schedule-runs.index'),
                    'run_now_url' => $this->safeRoute('editor.editorial-schedules.run-now', $nextSchedule),
                    'toggle_url' => $schedule->bulletinType ? $this->safeRoute('editor.bulletin-types.toggle-active', $schedule->bulletinType) : '#',
                ];
            })
            ->values()
            ->all();
    }

    private function scheduleSummary(Collection $schedules): array
    {
        $active = $schedules->filter(fn ($schedule) => $schedule->is_active);
        $first = $schedules->first();
        $next = $active->pluck('next_run_at')->filter()->sort()->first();

        return [
            'total' => $schedules->count(),
            'active' => $active->count(),
            'times' => $schedules
                ->map(fn ($schedule) => substr((string) ($schedule->run_time ?: $schedule->scheduled_time), 0, 5))
                ->filter()
                ->unique()
                ->values(),
            'active_times' => $active
                ->map(fn ($schedule) => substr((string) ($schedule->run_time ?: $schedule->scheduled_time), 0, 5))
                ->filter()
                ->unique()
                ->values(),
            'frequency' => $first?->run_frequency,
            'days' => array_values($first?->run_days ?? []),
            'month_day' => data_get($first?->metadata, 'month_day'),
            'next_run' => optional($next)?->toIso8601String(),
        ];
    }

    private function buildActionableQueue(Collection $schedules, Collection $bulletins, Carbon $now): Collection
    {
        $scheduleItems = $schedules
            ->filter(function ($schedule) use ($now) {
                if (! $this->isOperational($schedule)) {
                    return false;
                }

                return $this->needsAttention($schedule)
                    || ($schedule->next_run_at && $schedule->next_run_at->isPast())
                    || $this->hasFailedRunToday((int) $schedule->bulletin_type_id, $now);
            })
            ->map(fn ($schedule) => $this->queueScheduleItem($schedule));

        $configuredScheduleBulletinIds = $schedules
            ->filter(fn ($schedule) => (bool) $schedule->bulletin_type_id)
            ->pluck('bulletin_type_id')
            ->unique();

        $bulletinItems = $bulletins
            ->filter(fn (BulletinType $bulletin) => $bulletin->is_active
                && ! $this->activationService->isRunnable($bulletin)
                && (! $configuredScheduleBulletinIds->contains($bulletin->id) || ! $bulletin->primarySchedule?->is_active))
            ->map(fn (BulletinType $bulletin) => [
                'type' => 'bulletin',
                'type_label' => 'dashboard.queue.type.bulletin',
                'id' => 'bulletin-'.$bulletin->id,
                'bulletin' => $bulletin->name,
                'bulletin_id' => $bulletin->id,
                'provider' => $bulletin->preferredAiProvider?->name,
                'model' => $bulletin->preferredAiProvider?->default_model,
                'status' => 'missing_configuration',
                'next_action' => 'dashboard.action.configure',
                'scheduled_for' => optional($bulletin->updated_at)?->toIso8601String(),
                'view_url' => $this->safeRoute('editor.bulletin-types.show', $bulletin),
                'action_url' => $this->safeRoute('editor.bulletin-types.edit', $bulletin),
                'action_method' => null,
            ]);

        $promptWaiting = BulletinPromptRun::query()
            ->with('bulletinType:id,name,preferred_ai_provider_id', 'bulletinType.preferredAiProvider:id,name,default_model')
            ->where('status', 'waiting_ai_response')
            ->latest()
            ->limit(8)
            ->get()
            ->map(function ($run) {
                $provider = $run->bulletinType?->preferredAiProvider;

                return [
                    'type' => 'prompt',
                    'type_label' => 'dashboard.queue.type.aiResponse',
                    'id' => 'prompt-'.$run->id,
                    'bulletin' => $run->bulletinType?->name,
                    'bulletin_id' => $run->bulletin_type_id,
                    'provider' => $provider?->name,
                    'model' => $provider?->default_model,
                    'status' => 'waiting_ai_response',
                    'next_action' => 'dashboard.action.runAiCreateScript',
                    'scheduled_for' => optional($run->updated_at)?->toIso8601String(),
                    'view_url' => $this->safeRoute('editor.bulletin-prompt-runs.show', $run),
                    'action_url' => $this->safeRoute('editor.bulletin-prompt-runs.run-pipeline', $run),
                    'action_method' => 'post',
                ];
            });

        $scriptsPending = Script::query()
            ->with('bulletinPromptRun.bulletinType:id,name')
            ->where('review_status', 'pending')
            ->where('status', '!=', 'archived')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn ($script) => [
                'type' => 'script',
                'type_label' => 'dashboard.queue.type.script',
                'id' => 'script-'.$script->id,
                'bulletin' => $script->bulletinPromptRun?->bulletinType?->name ?? $script->title,
                'bulletin_id' => $script->bulletinPromptRun?->bulletin_type_id,
                'provider' => null,
                'model' => null,
                'status' => 'script_pending_review',
                'next_action' => 'dashboard.action.reviewScript',
                'scheduled_for' => optional($script->updated_at)?->toIso8601String(),
                'view_url' => $this->safeRoute('editor.scripts.review', $script),
                'action_url' => null,
                'action_method' => null,
            ]);

        $sourcesPending = SourceReference::query()
            ->withoutArchived()
            ->where('verification_status', 'pending')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn ($source) => [
                'type' => 'source',
                'type_label' => 'dashboard.queue.type.source',
                'id' => 'source-'.$source->id,
                'bulletin' => $source->title,
                'bulletin_id' => null,
                'provider' => $source->source_name,
                'model' => $source->source_domain,
                'status' => 'sources_pending_verification',
                'next_action' => 'dashboard.action.reviewSources',
                'scheduled_for' => optional($source->updated_at)?->toIso8601String(),
                'view_url' => $this->safeRoute('editor.source-references.index'),
                'action_url' => null,
                'action_method' => null,
            ]);

        return $scheduleItems
            ->concat($bulletinItems)
            ->concat($promptWaiting)
            ->concat($scriptsPending)
            ->concat($sourcesPending)
            ->values();
    }

    private function scriptsNeedingReview(): array
    {
        return Script::query()
            ->with('bulletinPromptRun.bulletinType:id,name')
            ->where('status', '!=', 'archived')
            ->where('review_status', 'pending')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Script $script) => [
                'id' => $script->id,
                'title' => $script->title,
                'bulletin' => $script->bulletinPromptRun?->bulletinType?->name,
                'review_status' => $script->review_status,
                'updated_at' => optional($script->updated_at)?->toIso8601String(),
                'url' => $this->safeRoute('editor.scripts.review', $script),
            ])
            ->all();
    }

    private function readyToApproveScripts(): array
    {
        return Script::query()
            ->with('bulletinPromptRun.bulletinType:id,name')
            ->where('status', '!=', 'archived')
            ->where('production_status', 'ready_for_production')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Script $script) => [
                'id' => $script->id,
                'title' => $script->title,
                'bulletin' => $script->bulletinPromptRun?->bulletinType?->name,
                'production_status' => $script->production_status,
                'updated_at' => optional($script->updated_at)?->toIso8601String(),
                'url' => $this->safeRoute('editor.scripts.show', $script),
            ])
            ->all();
    }

    private function buildCoverageOverview(Collection $bulletins): array
    {
        return [
            'explanation' => 'dashboard.coverage.explanation',
            'groups' => $bulletins
                ->groupBy(fn (BulletinType $bulletin) => ($bulletin->location?->name ?: 'dashboard.coverage.noLocation').'|'.($bulletin->newsCategory?->name ?: 'dashboard.coverage.noCategory'))
                ->map(function ($rows, $key) {
                    [$location, $category] = explode('|', $key);

                    return [
                        'location' => $location,
                        'category' => $category,
                        'active' => $rows->filter(fn (BulletinType $bulletin) => $this->activationService->isRunnable($bulletin))->count(),
                        'paused' => $rows->filter(fn (BulletinType $bulletin) => ! $this->activationService->isRunnable($bulletin))->count(),
                        'missing_provider' => $rows->filter(fn (BulletinType $bulletin) => ! $bulletin->preferred_ai_provider_id)->count(),
                        'missing_schedule' => $rows->filter(fn (BulletinType $bulletin) => ! $bulletin->primarySchedule)->count(),
                        'failed' => $rows->filter(fn (BulletinType $bulletin) => $this->hasFailedRunToday((int) $bulletin->id, now()))->count(),
                        'not_configured' => $rows->filter(fn (BulletinType $bulletin) => ! $bulletin->location || ! $bulletin->newsCategory)->count(),
                    ];
                })
                ->values(),
        ];
    }

    private function buildAiEngines(Carbon $now, Collection $bulletins): Collection
    {
        $query = AiProvider::query();

        if (Schema::hasColumn('ai_providers', 'is_testing')) {
            $query->where('is_testing', false);
        }

        return $query
            ->get(['id', 'name', 'default_model', 'is_active', 'supports_grounding', 'provider_category', 'rate_limited_until'])
            ->map(function (AiProvider $provider) use ($now, $bulletins) {
                $availability = $provider->rate_limited_until && $provider->rate_limited_until->isFuture()
                    ? 'rate_limited'
                    : ($provider->is_active ? 'available' : 'disabled');

                return [
                    'id' => $provider->id,
                    'name' => $provider->name,
                    'model' => $provider->default_model,
                    'purpose' => $provider->provider_category ?: 'dashboard.ai.purpose.editorialProvider',
                    'is_active' => (bool) $provider->is_active,
                    'grounded' => (bool) $provider->supports_grounding,
                    'env_key_configured' => $provider->hasConfiguredApiKey(),
                    'usage_today' => EditorialScheduleRun::query()
                        ->whereDate('scheduled_for', $now->toDateString())
                        ->whereHas('schedule.bulletinType', fn ($query) => $query->where('preferred_ai_provider_id', $provider->id))
                        ->count(),
                    'availability' => $availability,
                    'availability_label' => match ($availability) {
                        'available' => 'dashboard.ai.available',
                        'rate_limited' => 'dashboard.ai.rateLimited',
                        'disabled' => 'dashboard.ai.disabled',
                        default => 'dashboard.ai.unavailable',
                    },
                    'bulletins' => $bulletins
                        ->where('preferred_ai_provider_id', $provider->id)
                        ->pluck('name')
                        ->values(),
                ];
            })
            ->values();
    }

    private function buildLatestExecutions(): Collection
    {
        return EditorialScheduleRun::query()
            ->with([
                'schedule.bulletinType.preferredAiProvider:id,name,default_model',
                'bulletinPromptRun:id,title,status,generated_prompt,script_id',
                'script:id,title,production_status',
                'sourceReferences:id,editorial_schedule_run_id,verification_status',
            ])
            ->latest('scheduled_for')
            ->limit(12)
            ->get()
            ->map(function ($run) {
                $provider = $run->schedule?->bulletinType?->preferredAiProvider;
                $sourcesTotal = $run->sourceReferences->count();
                $sourcesPending = $run->sourceReferences->where('verification_status', 'pending')->count();
                $sourcesVerified = $run->sourceReferences->where('verification_status', 'verified')->count();
                $sourcesRejected = $run->sourceReferences
                    ->whereIn('verification_status', ['rejected', 'broken', 'missing'])
                    ->count();

                return [
                    'id' => $run->id,
                    'scheduled_for' => optional($run->scheduled_for)?->toIso8601String(),
                    'bulletin' => $run->schedule?->bulletinType?->name ?? $run->schedule?->name,
                    'status' => $run->status,
                    'provider' => $provider?->name,
                    'model' => $provider?->default_model,
                    'script' => $run->script ? [
                        'id' => $run->script->id,
                        'title' => $run->script->title,
                        'production_status' => $run->script->production_status,
                    ] : null,
                    'sources_total' => $sourcesTotal,
                    'sources_pending' => $sourcesPending,
                    'sources_verified' => $sourcesVerified,
                    'sources_rejected' => $sourcesRejected,
                    'sources_status' => $sourcesTotal === 0
                        ? 'no_sources'
                        : ($sourcesRejected > 0
                            ? 'sources_rejected'
                            : ($sourcesPending > 0
                                ? 'sources_pending_verification'
                                : 'sources_verified')),
                    'next_action' => $this->latestExecutionAction($run, $sourcesPending),
                    'pipeline' => $this->runPipeline($run, $sourcesPending),
                    'run_url' => $this->safeRoute('editor.editorial-schedule-runs.show', $run),
                    'prompt_run_url' => $run->bulletinPromptRun ? $this->safeRoute('editor.bulletin-prompt-runs.show', $run->bulletinPromptRun) : null,
                    'script_url' => $run->script ? $this->safeRoute('editor.scripts.show', $run->script) : null,
                    'sources_url' => $sourcesPending > 0 ? $this->safeRoute('editor.source-references.index') : null,
                    'action_url' => $this->latestExecutionActionUrl($run),
                    'action_method' => $this->latestExecutionActionUrl($run) ? 'post' : null,
                ];
            });
    }

    private function locationOverview(Collection $bulletins): array
    {
        return $bulletins
            ->groupBy(fn (BulletinType $bulletin) => $bulletin->location?->name ?: 'dashboard.coverage.noLocation')
            ->map(fn ($rows, $location) => [
                'location' => $location,
                'active_bulletins' => $rows->filter(fn (BulletinType $bulletin) => $this->activationService->isRunnable($bulletin))->count(),
                'next_run' => $rows->map(fn (BulletinType $bulletin) => $bulletin->primarySchedule?->next_run_at)->filter()->sort()->first()?->toIso8601String(),
                'missing_provider' => $rows->filter(fn (BulletinType $bulletin) => ! $bulletin->preferred_ai_provider_id)->count(),
                'issues' => $rows->filter(fn (BulletinType $bulletin) => ! $this->activationService->isRunnable($bulletin))->count(),
            ])
            ->values()
            ->all();
    }

    private function scheduleRunRow(EditorialSchedule $schedule): array
    {
        $provider = $schedule->bulletinType?->preferredAiProvider;

        return [
            'id' => $schedule->id,
            'schedule_id' => $schedule->id,
            'bulletin' => $schedule->bulletinType?->name ?? $schedule->name,
            'bulletin_id' => $schedule->bulletinType?->id,
            'location' => $schedule->location?->name,
            'category' => $schedule->newsCategory?->name,
            'provider' => $provider?->name,
            'model' => $provider?->default_model,
            'grounded' => (bool) $provider?->supports_grounding,
            'scheduled_for' => optional($schedule->next_run_at)?->toIso8601String(),
            'ai_manual_approval_required' => ! (bool) $schedule->auto_generate_ai_response,
            'run_now_url' => $this->safeRoute('editor.editorial-schedules.run-now', $schedule),
            'view_url' => $schedule->bulletinType ? $this->safeRoute('editor.bulletin-types.show', $schedule->bulletinType) : '#',
        ];
    }

    private function queueScheduleItem(EditorialSchedule $schedule): array
    {
        $provider = $schedule->bulletinType?->preferredAiProvider;
        $status = $this->queueStatus($schedule);

        return [
            'type' => 'schedule',
            'type_label' => 'dashboard.queue.type.schedule',
            'id' => 'schedule-'.$schedule->id,
            'bulletin' => $schedule->bulletinType?->name ?? $schedule->name,
            'bulletin_id' => $schedule->bulletinType?->id,
            'location' => $schedule->location?->name,
            'category' => $schedule->newsCategory?->name,
            'provider' => $provider?->name,
            'model' => $provider?->default_model,
            'status' => $status,
            'next_action' => $this->queueAction($schedule),
            'scheduled_for' => optional($schedule->next_run_at)?->toIso8601String(),
            'view_url' => $schedule->bulletinType ? $this->safeRoute('editor.bulletin-types.show', $schedule->bulletinType) : null,
            'action_url' => $status === 'schedule_overdue' ? $this->safeRoute('editor.editorial-schedules.run-now', $schedule) : null,
            'action_method' => $status === 'schedule_overdue' ? 'post' : null,
        ];
    }

    private function schedulePipeline(EditorialSchedule $schedule, mixed $lastRun): array
    {
        $pipeline = [];

        if (! $schedule->bulletinType) {
            $pipeline[] = 'dashboard.pipeline.missingBulletin';
        }

        $pipeline = array_merge($pipeline, $this->missingConfiguration($schedule));

        if (! $schedule->auto_generate_ai_response) {
            $pipeline[] = 'dashboard.pipeline.manualAiApproval';
        }

        if ($lastRun?->status) {
            $pipeline[] = $lastRun->status;
        }

        if ($schedule->next_run_at && $schedule->next_run_at->isPast()) {
            $pipeline[] = 'schedule_overdue';
        }

        if ($this->isOperational($schedule) && empty($pipeline)) {
            $pipeline[] = 'dashboard.pipeline.scheduled';
        }

        return $pipeline ?: ['dashboard.pipeline.incomplete'];
    }

    private function runPipeline(mixed $run, int $sourcesPending): array
    {
        $pipeline = [$run->status];

        $pipeline[] = $run->bulletinPromptRun ? 'prompt_ready' : 'dashboard.pipeline.noPromptRun';
        $pipeline[] = $run->script ? ($run->script->production_status ?: 'script_created') : 'dashboard.pipeline.noScript';
        $pipeline[] = $sourcesPending > 0 ? 'sources_pending_verification' : 'sources_verified';

        return array_values(array_filter($pipeline));
    }

    private function latestExecutionAction(mixed $run, int $sourcesPending): string
    {
        if ($run->status === 'failed') {
            return 'dashboard.action.retry';
        }

        if ($sourcesPending > 0) {
            return 'dashboard.action.reviewSources';
        }

        if ($run->script) {
            return 'dashboard.action.viewScript';
        }

        if ($run->bulletinPromptRun) {
            return filled($run->bulletinPromptRun->generated_prompt)
                ? 'dashboard.action.runAiCreateScript'
                : 'dashboard.action.viewPromptRun';
        }

        return 'dashboard.action.viewRun';
    }

    private function latestExecutionActionUrl(mixed $run): ?string
    {
        if ($run->status === 'failed' && $run->schedule) {
            return $this->safeRoute('editor.editorial-schedules.run-now', $run->schedule);
        }

        if ($run->bulletinPromptRun && ! $run->script && filled($run->bulletinPromptRun->generated_prompt)) {
            return $this->safeRoute('editor.bulletin-prompt-runs.run-pipeline', $run->bulletinPromptRun);
        }

        return null;
    }

    private function queueStatus(EditorialSchedule $schedule): string
    {
        if (! $schedule->bulletinType) {
            return 'missing_bulletin_type';
        }

        $missing = $this->missingConfiguration($schedule);

        if (in_array('bulletinTypes.health.missingProvider', $missing, true)) {
            return 'missing_ai_provider';
        }

        if (in_array('bulletinTypes.health.missingSchedule', $missing, true)) {
            return 'missing_schedule';
        }

        if ($missing !== []) {
            return 'missing_configuration';
        }

        if ($schedule->next_run_at && $schedule->next_run_at->isPast()) {
            return 'schedule_overdue';
        }

        if ($this->hasFailedRunToday((int) $schedule->bulletin_type_id, now())) {
            return 'failed_today';
        }

        return 'scheduled';
    }

    private function queueAction(EditorialSchedule $schedule): string
    {
        if ($this->needsAttention($schedule)) {
            return 'dashboard.action.configure';
        }

        if ($schedule->next_run_at && $schedule->next_run_at->isPast()) {
            return 'dashboard.action.runNow';
        }

        if ($this->hasFailedRunToday((int) $schedule->bulletin_type_id, now())) {
            return 'dashboard.action.reviewFailure';
        }

        return 'dashboard.action.viewSchedule';
    }

    private function hasFailedRunToday(int $bulletinId, Carbon $now): bool
    {
        if ($bulletinId <= 0) {
            return false;
        }

        return EditorialScheduleRun::query()
            ->whereHas('schedule', fn ($query) => $query->where('bulletin_type_id', $bulletinId))
            ->whereDate('scheduled_for', $now->toDateString())
            ->where('status', 'failed')
            ->exists();
    }

    private function needsAttention(EditorialSchedule $schedule): bool
    {
        return ! $schedule->bulletinType || $this->missingConfiguration($schedule) !== [];
    }

    private function isOperational(EditorialSchedule $schedule): bool
    {
        return (bool) ($schedule->is_active && $schedule->bulletinType?->is_active);
    }

    private function isEligibleForScheduling(EditorialSchedule $schedule): bool
    {
        return $this->isOperational($schedule) && ! $this->needsAttention($schedule);
    }

    private function missingConfiguration(EditorialSchedule $schedule): array
    {
        if (! $schedule->bulletinType) {
            return ['dashboard.pipeline.missingBulletin'];
        }

        return $this->activationService->missingConfiguration($schedule->bulletinType);
    }

    private function executionRow(EditorialScheduleRun $run): array
    {
        $provider = $run->schedule?->bulletinType?->preferredAiProvider;
        $sourcesTotal = $run->sourceReferences->count();
        $sourcesPending = $run->sourceReferences->where('verification_status', 'pending')->count();
        $sourcesVerified = $run->sourceReferences->where('verification_status', 'verified')->count();
        $sourcesRejected = $run->sourceReferences
            ->whereIn('verification_status', ['rejected', 'broken', 'missing'])
            ->count();

        return [
            'type' => 'execution',
            'id' => $run->id,
            'schedule_id' => $run->editorial_schedule_id,
            'scheduled_for' => optional($run->scheduled_for)?->toIso8601String(),
            'bulletin' => $run->schedule?->bulletinType?->name ?? $run->schedule?->name,
            'status' => $run->status,
            'provider' => $provider?->name,
            'model' => $provider?->default_model,
            'sources_total' => $sourcesTotal,
            'sources_pending' => $sourcesPending,
            'sources_verified' => $sourcesVerified,
            'sources_rejected' => $sourcesRejected,
            'next_action' => $this->latestExecutionAction($run, $sourcesPending),
            'pipeline' => $this->runPipeline($run, $sourcesPending),
            'run_url' => $this->safeRoute('editor.editorial-schedule-runs.show', $run),
            'prompt_run_url' => $run->bulletinPromptRun ? $this->safeRoute('editor.bulletin-prompt-runs.show', $run->bulletinPromptRun) : null,
            'script_url' => $run->script ? $this->safeRoute('editor.scripts.show', $run->script) : null,
            'sources_url' => $sourcesPending > 0 ? $this->safeRoute('editor.source-references.index') : null,
            'action_url' => $this->latestExecutionActionUrl($run),
            'action_method' => $this->latestExecutionActionUrl($run) ? 'post' : null,
        ];
    }

    private function safeRoute(string $name, mixed $parameters = []): string
    {
        if (! Route::has($name)) {
            return '#';
        }

        return route($name, $parameters);
    }
}
