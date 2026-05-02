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
    public function __construct(private readonly BulletinMapService $mapService) {}

    public function build(): array
    {
        $today = now();

        $schedules = EditorialSchedule::query()
            ->with([
                'bulletinType:id,name,is_active,location_id,news_category_id,ai_provider_id',
                'bulletinType.aiProvider:id,name,default_model,supports_grounding,is_active,provider_category,rate_limited_until',
                'location:id,name',
                'newsCategory:id,name',
                'runs' => fn ($q) => $q->latest('scheduled_for')->limit(1),
            ])
            ->get();

        $bulletins = BulletinType::query()
            ->with(['aiProvider:id,name,default_model'])
            ->get(['id', 'name', 'is_active', 'ai_provider_id']);

        return [
            'headerActions' => [
                [
                    'key' => 'automation',
                    'label' => 'Ver automatización',
                    'href' => $this->safeRoute('editor.automation.index'),
                ],
                [
                    'key' => 'createBulletin',
                    'label' => 'Crear informativo',
                    'href' => $this->safeRoute('editor.bulletin-types.create'),
                ],
                [
                    'key' => 'fullMap',
                    'label' => 'Ver mapa completo',
                    'href' => $this->safeRoute('editor.world-map.index'),
                ],
            ],
            'summaryCards' => $this->summaryCards($schedules, $today),
            'mapOverview' => [
                'markers' => $this->mapService->getEditorMapData(),
                'mapRoute' => $this->safeRoute('editor.world-map.index'),
            ],
            'scheduledBulletins' => $this->scheduledBulletins($schedules),
            'actionableQueue' => $this->buildActionableQueue($schedules, $today)->values(),
            'aiEngines' => $this->buildAiEngines($today, $bulletins)->values(),
            'coverageByLocationTopic' => $this->buildCoverageOverview($schedules),
            'latestExecutions' => $this->buildLatestExecutions()->values(),
        ];
    }

    private function summaryCards(Collection $schedules, Carbon $today): array
    {
        $summary = $this->buildSummary($schedules, $today);

        return [
            [
                'key' => 'activeBulletins',
                'label' => 'Informativos activos',
                'value' => $summary['activeBulletins'],
            ],
            [
                'key' => 'scheduledToday',
                'label' => 'Programados hoy',
                'value' => $summary['scheduledToday'],
            ],
            [
                'key' => 'overdueSchedules',
                'label' => 'Programaciones vencidas',
                'value' => $summary['overdueSchedules'],
            ],
            [
                'key' => 'failedRunsToday',
                'label' => 'Fallos hoy',
                'value' => $summary['failedRunsToday'],
            ],
            [
                'key' => 'scriptsPendingReview',
                'label' => 'Guiones pendientes de revisión',
                'value' => $summary['scriptsPendingReview'],
            ],
            [
                'key' => 'sourcesPendingVerification',
                'label' => 'Fuentes pendientes',
                'value' => $summary['sourcesPendingVerification'],
            ],
            [
                'key' => 'readyForProduction',
                'label' => 'Listos para producción',
                'value' => $summary['readyForProduction'],
            ],
        ];
    }

    private function buildSummary(Collection $schedules, Carbon $today): array
    {
        $queue = $this->buildActionableQueue($schedules, $today);

        return [
            'activeBulletins' => $schedules
                ->filter(fn ($schedule) => $schedule->is_active && $schedule->bulletinType?->is_active)
                ->count(),

            'scheduledToday' => $schedules
                ->filter(fn ($schedule) => $schedule->is_active && $schedule->next_run_at && $schedule->next_run_at->isToday())
                ->count(),

            'overdueSchedules' => $schedules
                ->filter(fn ($schedule) => $schedule->is_active && $schedule->next_run_at && $schedule->next_run_at->isPast())
                ->count(),

            'failedRunsToday' => EditorialScheduleRun::query()
                ->whereDate('scheduled_for', $today->toDateString())
                ->where('status', 'failed')
                ->count(),

            'scriptsPendingReview' => Script::query()
                ->where('review_status', 'pending')
                ->where('status', '!=', 'archived')
                ->count(),

            'sourcesPendingVerification' => SourceReference::query()
                ->withoutArchived()
                ->where('verification_status', 'pending')
                ->count(),

            'readyForProduction' => Script::query()
                ->where('status', '!=', 'archived')
                ->where('production_status', 'ready_for_production')
                ->count(),
        ];
    }

    private function buildActionableQueue(Collection $schedules, Carbon $today): Collection
    {
        $scheduleItems = $schedules
            ->filter(function ($schedule) use ($today) {
                if (! $schedule->bulletinType) {
                    return true;
                }

                if (! $schedule->is_active || ! $schedule->bulletinType->is_active) {
                    return $this->needsAttention($schedule);
                }

                return $this->needsAttention($schedule)
                    || ($schedule->next_run_at && $schedule->next_run_at->isPast())
                    || ($schedule->next_run_at && $schedule->next_run_at->isToday())
                    || $this->hasFailedRunToday((int) $schedule->bulletin_type_id, $today);
            })
            ->map(function ($schedule) {
                $status = $this->queueStatus($schedule);

                return [
                    'type' => 'schedule',
                    'type_label' => 'Programación',
                    'id' => 'schedule-'.$schedule->id,
                    'bulletin' => $schedule->bulletinType?->name ?? $schedule->name,
                    'bulletin_id' => $schedule->bulletinType?->id,
                    'location' => $schedule->location?->name,
                    'category' => $schedule->newsCategory?->name,
                    'provider' => $schedule->bulletinType?->aiProvider?->name,
                    'model' => $schedule->bulletinType?->aiProvider?->default_model,
                    'status' => $status,
                    'next_action' => $this->queueAction($schedule),
                    'scheduled_for' => optional($schedule->next_run_at)?->toIso8601String(),
                    'view_url' => $schedule->bulletinType
                        ? $this->safeRoute('editor.bulletin-types.show', $schedule->bulletinType)
                        : null,
                    'action_url' => $schedule->bulletinType
                        ? $this->safeRoute('editor.bulletin-types.run-now', $schedule->bulletinType)
                        : null,
                ];
            });

        $promptWaiting = BulletinPromptRun::query()
            ->with('bulletinType:id,name')
            ->where('status', 'waiting_ai_response')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn ($run) => [
                'type' => 'prompt',
                'type_label' => 'Respuesta IA',
                'id' => 'prompt-'.$run->id,
                'bulletin' => $run->bulletinType?->name,
                'bulletin_id' => $run->bulletin_type_id,
                'provider' => null,
                'model' => null,
                'status' => 'waiting_ai_response',
                'next_action' => 'Generar respuesta IA',
                'scheduled_for' => optional($run->updated_at)?->toIso8601String(),
                'view_url' => $this->safeRoute('editor.bulletin-prompt-runs.show', $run),
                'action_url' => null,
            ]);

        $scriptsPending = Script::query()
            ->with('bulletinPromptRun.bulletinType:id,name')
            ->where('review_status', 'pending')
            ->where('status', '!=', 'archived')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn ($script) => [
                'type' => 'script',
                'type_label' => 'Guion',
                'id' => 'script-'.$script->id,
                'bulletin' => $script->bulletinPromptRun?->bulletinType?->name,
                'bulletin_id' => $script->bulletinPromptRun?->bulletin_type_id,
                'provider' => null,
                'model' => null,
                'status' => 'script_pending_review',
                'next_action' => 'Revisar guion',
                'scheduled_for' => optional($script->updated_at)?->toIso8601String(),
                'view_url' => $this->safeRoute('editor.scripts.review', $script),
                'action_url' => null,
            ]);

        $sourcesPending = SourceReference::query()
            ->withoutArchived()
            ->where('verification_status', 'pending')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn ($source) => [
                'type' => 'source',
                'type_label' => 'Fuente',
                'id' => 'source-'.$source->id,
                'bulletin' => $source->title,
                'bulletin_id' => null,
                'provider' => $source->source_name,
                'model' => $source->source_domain,
                'status' => 'sources_pending_verification',
                'next_action' => 'Verificar fuente',
                'scheduled_for' => optional($source->updated_at)?->toIso8601String(),
                'view_url' => $this->safeRoute('editor.source-references.index'),
                'action_url' => null,
            ]);

        $readyScripts = Script::query()
            ->with('bulletinPromptRun.bulletinType:id,name')
            ->where('status', '!=', 'archived')
            ->where('production_status', 'ready_for_production')
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn ($script) => [
                'type' => 'production',
                'type_label' => 'Producción',
                'id' => 'production-'.$script->id,
                'bulletin' => $script->bulletinPromptRun?->bulletinType?->name ?? $script->title,
                'bulletin_id' => $script->bulletinPromptRun?->bulletin_type_id,
                'provider' => null,
                'model' => null,
                'status' => 'ready_for_production',
                'next_action' => 'Preparar producción',
                'scheduled_for' => optional($script->updated_at)?->toIso8601String(),
                'view_url' => $this->safeRoute('editor.scripts.show', $script),
                'action_url' => null,
            ]);

        return $scheduleItems
            ->concat($promptWaiting)
            ->concat($scriptsPending)
            ->concat($sourcesPending)
            ->concat($readyScripts)
            ->values();
    }

    private function scheduledBulletins(Collection $schedules): array
    {
        return $schedules
            ->map(function ($schedule) {
                $lastRun = $schedule->runs->first();

                return [
                    'id' => $schedule->id,
                    'bulletin' => $schedule->bulletinType?->name ?? $schedule->name ?? '—',
                    'bulletin_url' => $schedule->bulletinType
                        ? $this->safeRoute('editor.bulletin-types.show', $schedule->bulletinType)
                        : '#',
                    'location' => $schedule->location?->name,
                    'category' => $schedule->newsCategory?->name,
                    'provider' => $schedule->bulletinType?->aiProvider?->name,
                    'model' => $schedule->bulletinType?->aiProvider?->default_model,
                    'frequency' => $schedule->run_frequency,
                    'time' => $schedule->run_time ?: $schedule->scheduled_time,
                    'next_run' => optional($schedule->next_run_at)?->toIso8601String(),
                    'last_run' => optional($schedule->last_run_at ?: $lastRun?->scheduled_for)?->toIso8601String(),
                    'last_result' => $lastRun?->status,
                    'is_on' => (bool) ($schedule->is_active && $schedule->bulletinType?->is_active),
                    'pipeline' => $this->schedulePipeline($schedule, $lastRun),
                    'view_url' => $schedule->bulletinType
                        ? $this->safeRoute('editor.bulletin-types.show', $schedule->bulletinType)
                        : '#',
                    'runs_url' => $this->safeRoute('editor.editorial-schedule-runs.index'),
                    'run_now_url' => $schedule->bulletinType
                        ? $this->safeRoute('editor.bulletin-types.run-now', $schedule->bulletinType)
                        : '#',
                ];
            })
            ->values()
            ->all();
    }

    private function buildCoverageOverview(Collection $schedules): array
    {
        return [
            'explanation' => 'Resumen operativo por ubicación y categoría para detectar huecos de cobertura y configuración.',
            'groups' => $schedules
                ->groupBy(fn ($schedule) => ($schedule->location?->name ?: 'Sin ubicación').'|'.($schedule->newsCategory?->name ?: 'Sin categoría'))
                ->map(function ($rows, $key) {
                    [$location, $category] = explode('|', $key);

                    return [
                        'location' => $location,
                        'category' => $category,
                        'active' => $rows->filter(fn ($schedule) => $schedule->is_active && $schedule->bulletinType?->is_active)->count(),
                        'paused' => $rows->filter(fn ($schedule) => ! $schedule->is_active || ! $schedule->bulletinType?->is_active)->count(),
                        'missing_provider' => $rows->filter(fn ($schedule) => ! $schedule->bulletinType?->ai_provider_id)->count(),
                        'missing_schedule' => $rows->filter(fn ($schedule) => ! $schedule->run_frequency || ! ($schedule->run_time ?: $schedule->scheduled_time))->count(),
                        'failed' => $rows->filter(fn ($schedule) => $this->hasFailedRunToday((int) $schedule->bulletin_type_id, now()))->count(),
                        'not_configured' => $rows->filter(fn ($schedule) => ! $schedule->bulletinType || ! $schedule->location || ! $schedule->newsCategory)->count(),
                    ];
                })
                ->values(),
        ];
    }

    private function buildAiEngines(Carbon $today, Collection $bulletins): Collection
    {
        $query = AiProvider::query();

        if (Schema::hasColumn('ai_providers', 'is_testing')) {
            $query->where('is_testing', false);
        }

        return $query
            ->get(['id', 'name', 'default_model', 'is_active', 'supports_grounding', 'provider_category', 'rate_limited_until'])
            ->map(function (AiProvider $provider) use ($today, $bulletins) {
                $availability = $provider->rate_limited_until && $provider->rate_limited_until->isFuture()
                    ? 'rate_limited'
                    : ($provider->is_active ? 'available' : 'disabled');

                return [
                    'id' => $provider->id,
                    'name' => $provider->name,
                    'model' => $provider->default_model,
                    'purpose' => $provider->provider_category ?: 'Proveedor editorial',
                    'is_active' => (bool) $provider->is_active,
                    'grounded' => (bool) $provider->supports_grounding,
                    'usage_today' => EditorialScheduleRun::query()
                        ->whereDate('scheduled_for', $today->toDateString())
                        ->whereHas('schedule.bulletinType', fn ($query) => $query->where('ai_provider_id', $provider->id))
                        ->count(),
                    'availability' => $availability,
                    'availability_label' => match ($availability) {
                        'available' => 'Disponible',
                        'rate_limited' => 'Limitado temporalmente',
                        'disabled' => 'Desactivado',
                        default => 'No disponible',
                    },
                    'bulletins' => $bulletins
                        ->where('ai_provider_id', $provider->id)
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
                'schedule.bulletinType.aiProvider:id,name,default_model',
                'script:id,title,production_status',
                'sourceReferences:id,editorial_schedule_run_id,verification_status',
            ])
            ->latest('scheduled_for')
            ->limit(12)
            ->get()
            ->map(function ($run) {
                $sourcesPending = $run->sourceReferences
                    ->where('verification_status', 'pending')
                    ->count();

                return [
                    'id' => $run->id,
                    'scheduled_for' => optional($run->scheduled_for)?->toIso8601String(),
                    'bulletin' => $run->schedule?->bulletinType?->name ?? $run->schedule?->name,
                    'status' => $run->status,
                    'provider' => $run->schedule?->bulletinType?->aiProvider?->name,
                    'model' => $run->schedule?->bulletinType?->aiProvider?->default_model,
                    'script' => $run->script ? [
                        'id' => $run->script->id,
                        'title' => $run->script->title,
                        'production_status' => $run->script->production_status,
                    ] : null,
                    'sources_pending' => $sourcesPending,
                    'sources_status' => $sourcesPending > 0 ? 'sources_pending_verification' : 'sources_verified',
                    'sources_label' => $sourcesPending > 0
                        ? $sourcesPending.' pendientes'
                        : 'Verificadas',
                    'next_action' => $run->status === 'failed'
                        ? 'Reintentar'
                        : ($run->script ? 'Revisar guion' : 'Ver ejecución'),
                    'pipeline' => $this->runPipeline($run, $sourcesPending),
                    'run_url' => $this->safeRoute('editor.editorial-schedule-runs.show', $run),
                    'script_url' => $run->script
                        ? $this->safeRoute('editor.scripts.show', $run->script)
                        : null,
                ];
            });
    }

    private function schedulePipeline(EditorialSchedule $schedule, mixed $lastRun): array
    {
        $pipeline = [];

        if (! $schedule->bulletinType) {
            $pipeline[] = 'Sin informativo';
        }

        if (! $schedule->bulletinType?->ai_provider_id) {
            $pipeline[] = 'Sin proveedor IA';
        }

        if (! $schedule->run_frequency || ! ($schedule->run_time ?: $schedule->scheduled_time)) {
            $pipeline[] = 'Sin horario';
        }

        if ($lastRun?->status) {
            $pipeline[] = $lastRun->status;
        }

        if ($schedule->next_run_at && $schedule->next_run_at->isPast()) {
            $pipeline[] = 'Vencido';
        }

        if ($schedule->is_active && $schedule->bulletinType?->is_active && empty($pipeline)) {
            $pipeline[] = 'Programado';
        }

        return $pipeline ?: ['Pendiente de configurar'];
    }

    private function runPipeline(mixed $run, int $sourcesPending): array
    {
        $pipeline = [$run->status];

        if ($run->script) {
            $pipeline[] = $run->script->production_status ?: 'Guion generado';
        } else {
            $pipeline[] = 'Sin guion';
        }

        $pipeline[] = $sourcesPending > 0
            ? 'Fuentes pendientes'
            : 'Fuentes verificadas';

        return array_values(array_filter($pipeline));
    }

    private function queueStatus(EditorialSchedule $schedule): string
    {
        if (! $schedule->bulletinType) {
            return 'missing_bulletin_type';
        }

        if (! $schedule->bulletinType?->ai_provider_id) {
            return 'missing_ai_provider';
        }

        if (! $schedule->run_frequency || ! ($schedule->run_time ?: $schedule->scheduled_time)) {
            return 'missing_schedule';
        }

        if (! $schedule->is_active || ! $schedule->bulletinType?->is_active) {
            return 'paused';
        }

        if ($schedule->next_run_at && $schedule->next_run_at->isPast()) {
            return 'schedule_overdue';
        }

        if ($this->hasFailedRunToday((int) $schedule->bulletin_type_id, now())) {
            return 'failed_today';
        }

        return 'scheduled_today';
    }

    private function queueAction(EditorialSchedule $schedule): string
    {
        if ($this->needsAttention($schedule)) {
            return 'Completar configuración';
        }

        if ($schedule->next_run_at && $schedule->next_run_at->isPast()) {
            return 'Ejecutar ahora';
        }

        if ($this->hasFailedRunToday((int) $schedule->bulletin_type_id, now())) {
            return 'Revisar fallo';
        }

        return 'Ver programación';
    }

    private function hasFailedRunToday(int $bulletinId, Carbon $today): bool
    {
        if ($bulletinId <= 0) {
            return false;
        }

        return EditorialScheduleRun::query()
            ->whereHas('schedule', fn ($query) => $query->where('bulletin_type_id', $bulletinId))
            ->whereDate('scheduled_for', $today->toDateString())
            ->where('status', 'failed')
            ->exists();
    }

    private function needsAttention(EditorialSchedule $schedule): bool
    {
        return ! $schedule->bulletinType
            || ! $schedule->bulletinType?->ai_provider_id
            || ! $schedule->run_frequency
            || ! ($schedule->run_time ?: $schedule->scheduled_time);
    }

    private function safeRoute(string $name, mixed $parameters = []): string
    {
        if (! Route::has($name)) {
            return '#';
        }

        return route($name, $parameters);
    }
}