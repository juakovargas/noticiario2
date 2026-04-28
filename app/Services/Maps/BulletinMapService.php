<?php

namespace App\Services\Maps;

use App\Models\Location;
use Illuminate\Support\Collection;

class BulletinMapService
{
    public function getEditorMapData(): array
    {
        return $this->buildData(includeAllConfiguredLocations: false, includeInactiveBulletins: false, includeUrls: true, panel: 'editor');
    }

    public function getAdminMapData(): array
    {
        return $this->buildData(includeAllConfiguredLocations: true, includeInactiveBulletins: true, includeUrls: true, panel: 'admin');
    }

    public function getViewerMapData(): array
    {
        return $this->buildData(includeAllConfiguredLocations: false, includeInactiveBulletins: false, includeUrls: false, panel: 'viewer');
    }

    private function buildData(bool $includeAllConfiguredLocations, bool $includeInactiveBulletins, bool $includeUrls, string $panel): array
    {
        $locations = Location::query()
            ->where('show_on_map', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with([
                'bulletinTypes' => function ($query) use ($includeInactiveBulletins): void {
                    $query->select(['id', 'name', 'location_id', 'is_active', 'edition_type', 'news_category_id', 'language_id'])
                        ->with(['newsCategory:id,name', 'language:id,name']);

                    if (! $includeInactiveBulletins) {
                        $query->where('is_active', true);
                    }
                },
                'bulletinTypes.promptRuns' => fn ($query) => $query
                    ->select(['id', 'bulletin_type_id', 'status', 'updated_at'])
                    ->orderByDesc('updated_at'),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $locations
            ->map(function (Location $location) use ($includeAllConfiguredLocations, $includeUrls, $panel): ?array {
                $bulletins = $location->bulletinTypes;

                if (! $includeAllConfiguredLocations && $bulletins->isEmpty()) {
                    return null;
                }

                $allRuns = $bulletins->flatMap->promptRuns;
                $activeRuns = $allRuns->where('status', '!=', 'archived');
                $latestRun = $activeRuns->sortByDesc('updated_at')->first();

                $pendingCount = $activeRuns->filter(fn ($run) => in_array($run->status, ['draft', 'prompt_ready', 'waiting_ai_response', 'response_received'], true))->count();
                $completedCount = $activeRuns->filter(fn ($run) => in_array($run->status, ['script_created', 'completed'], true))->count();
                $archivedCount = $allRuns->where('status', 'archived')->count();

                return [
                    'location_id' => $location->id,
                    'location_name' => $location->name,
                    'location_type' => $location->type,
                    'latitude' => (float) $location->latitude,
                    'longitude' => (float) $location->longitude,
                    'map_zoom' => $location->map_zoom,
                    'marker_color' => $location->marker_color ?: $this->resolveMarkerColor($activeRuns),
                    'marker_label' => $location->marker_label ?: $location->name,
                    'bulletin_count' => $bulletins->count(),
                    'active_bulletin_count' => $bulletins->where('is_active', true)->count(),
                    'inactive_bulletin_count' => $bulletins->where('is_active', false)->count(),
                    'pending_prompt_runs_count' => $pendingCount,
                    'completed_prompt_runs_count' => $completedCount,
                    'archived_prompt_runs_count' => $archivedCount,
                    'latest_prompt_run_status' => $latestRun?->status,
                    'latest_prompt_run_at' => $latestRun?->updated_at?->toISOString(),
                    'bulletins' => $bulletins->map(fn ($bulletin) => [
                        'id' => $bulletin->id,
                        'name' => $bulletin->name,
                        'status' => $bulletin->is_active ? 'active' : 'inactive',
                        'edition_type' => $bulletin->edition_type,
                        'category' => $bulletin->newsCategory?->name,
                        'language' => $bulletin->language?->name,
                        'url' => $this->resolveBulletinUrl($panel, $includeUrls, $bulletin->id, $location->id),
                    ])->values()->all(),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function resolveBulletinUrl(string $panel, bool $includeUrls, int $bulletinTypeId, int $locationId): ?string
    {
        if (! $includeUrls) {
            return null;
        }

        return match ($panel) {
            'editor' => route('editor.bulletin-types.show', $bulletinTypeId),
            'admin' => route('editor.bulletin-types.index', ['location_id' => $locationId]),
            default => null,
        };
    }

    private function resolveMarkerColor(Collection $activeRuns): string
    {
        if ($activeRuns->isEmpty()) {
            return 'blue';
        }

        $statuses = $activeRuns->pluck('status')->all();

        if (array_intersect($statuses, ['failed', 'cancelled']) !== []) {
            return 'red';
        }

        $hasPending = array_intersect($statuses, ['draft', 'prompt_ready', 'waiting_ai_response', 'response_received']) !== [];
        $hasCompleted = array_intersect($statuses, ['completed', 'script_created']) !== [];

        if ($hasCompleted && ! $hasPending) {
            return 'green';
        }

        if ($hasPending) {
            return 'amber';
        }

        return 'blue';
    }
}
