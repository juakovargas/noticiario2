<?php

namespace App\Services\Editor;

use App\Models\BulletinType;
use App\Models\EditorialSchedule;
use App\Services\Scheduling\EditorialScheduleRunner;

class BulletinTypeActivationService
{
    public function __construct(private readonly EditorialScheduleRunner $runner)
    {
    }

    public function missingConfiguration(BulletinType $bulletinType): array
    {
        $bulletinType->loadMissing(['preferredAiProvider', 'location', 'newsCategory', 'language', 'schedules']);

        $missing = [];
        $provider = $bulletinType->preferredAiProvider;

        if (! $provider) {
            $missing[] = 'bulletinTypes.health.missingProvider';
        } elseif (! $provider->is_active) {
            $missing[] = 'bulletinTypes.health.providerInactive';
        }

        if (! $this->hasValidSchedule($bulletinType)) {
            $missing[] = 'bulletinTypes.health.missingSchedule';
        }

        if (! $bulletinType->location_id) {
            $missing[] = 'bulletinTypes.health.missingLocation';
        }

        if (! $bulletinType->news_category_id) {
            $missing[] = 'bulletinTypes.health.missingCategory';
        }

        if (! $bulletinType->language_id) {
            $missing[] = 'bulletinTypes.health.missingLanguage';
        }

        if (! $bulletinType->target_duration_seconds) {
            $missing[] = 'bulletinTypes.health.missingDuration';
        }

        return array_values(array_unique($missing));
    }

    public function isComplete(BulletinType $bulletinType): bool
    {
        return $this->missingConfiguration($bulletinType) === [];
    }

    public function isRunnable(BulletinType $bulletinType): bool
    {
        $schedule = $this->activatableSchedule($bulletinType);

        return (bool) ($bulletinType->is_active && $schedule?->is_active && $this->isComplete($bulletinType));
    }

    public function activatableSchedule(BulletinType $bulletinType): ?EditorialSchedule
    {
        $bulletinType->loadMissing('schedules');

        return $bulletinType->schedules
            ->sortByDesc(fn (EditorialSchedule $schedule) => (bool) $schedule->is_primary)
            ->first(fn (EditorialSchedule $schedule) => $this->isScheduleConfigured($schedule));
    }

    public function activate(BulletinType $bulletinType): void
    {
        $schedule = $this->activatableSchedule($bulletinType);

        $bulletinType->forceFill([
            'is_active' => true,
            'default_schedule_is_active' => true,
        ])->save();

        if ($schedule) {
            $schedule->forceFill([
                'is_active' => true,
                'next_run_at' => $schedule->next_run_at ?: $this->runner->calculateNextRunAt($schedule),
            ])->save();
        }
    }

    public function deactivate(BulletinType $bulletinType): void
    {
        $bulletinType->forceFill([
            'is_active' => false,
            'default_schedule_is_active' => false,
        ])->save();

        $bulletinType->schedules()->update([
            'is_active' => false,
            'next_run_at' => null,
        ]);
    }

    private function hasValidSchedule(BulletinType $bulletinType): bool
    {
        return $this->activatableSchedule($bulletinType) !== null;
    }

    private function isScheduleConfigured(EditorialSchedule $schedule): bool
    {
        $time = $schedule->run_time ?: $schedule->scheduled_time;

        if (! $schedule->run_frequency || ! $time || ! $schedule->timezone) {
            return false;
        }

        return in_array($schedule->timezone, timezone_identifiers_list(), true);
    }
}
