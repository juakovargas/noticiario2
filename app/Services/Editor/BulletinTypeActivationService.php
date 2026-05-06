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
        $schedule = $this->runnableSchedule($bulletinType);

        return (bool) ($bulletinType->is_active && $schedule && $this->isComplete($bulletinType));
    }

    public function activatableSchedule(BulletinType $bulletinType): ?EditorialSchedule
    {
        $bulletinType->loadMissing('schedules');

        return $bulletinType->schedules
            ->filter(fn (EditorialSchedule $schedule) => $schedule->is_active)
            ->sortByDesc(fn (EditorialSchedule $schedule) => (bool) $schedule->is_primary)
            ->first(fn (EditorialSchedule $schedule) => $this->isScheduleConfigured($schedule));
    }

    public function runnableSchedule(BulletinType $bulletinType): ?EditorialSchedule
    {
        $bulletinType->loadMissing('schedules');

        return $bulletinType->schedules
            ->filter(fn (EditorialSchedule $schedule) => $schedule->is_active)
            ->sortByDesc(fn (EditorialSchedule $schedule) => (bool) $schedule->is_primary)
            ->first(fn (EditorialSchedule $schedule) => $this->isScheduleConfigured($schedule));
    }

    public function activate(BulletinType $bulletinType): void
    {
        $bulletinType->forceFill([
            'is_active' => true,
            'default_schedule_is_active' => true,
        ])->save();

        $bulletinType->schedules
            ->filter(fn (EditorialSchedule $schedule) => $schedule->is_active && $this->isScheduleConfigured($schedule))
            ->each(fn (EditorialSchedule $schedule) => $schedule->forceFill([
                'next_run_at' => $schedule->next_run_at ?: $this->runner->calculateNextRunAt($schedule),
            ])->save());
    }

    public function deactivate(BulletinType $bulletinType): void
    {
        $bulletinType->forceFill([
            'is_active' => false,
            'default_schedule_is_active' => false,
        ])->save();
    }

    private function hasValidSchedule(BulletinType $bulletinType): bool
    {
        return $this->activatableSchedule($bulletinType) !== null;
    }

    private function isScheduleConfigured(EditorialSchedule $schedule): bool
    {
        $frequency = $schedule->run_frequency ?: $schedule->frequency_type;
        $time = $schedule->run_time ?: $schedule->scheduled_time;

        if (! $frequency || ! $time || ! preg_match('/^\d{2}:\d{2}(:\d{2})?$/', (string) $time) || ! $schedule->timezone) {
            return false;
        }

        if (! in_array($schedule->timezone, timezone_identifiers_list(), true)) {
            return false;
        }

        if (in_array($frequency, ['selected_days', 'weekly', 'custom'], true) && empty($schedule->run_days)) {
            return false;
        }

        if ($frequency === 'monthly') {
            $monthDay = (int) data_get($schedule->metadata, 'month_day');

            return $monthDay >= 1 && $monthDay <= 31;
        }

        if ($frequency === 'once') {
            return filled($schedule->scheduled_date);
        }

        return true;
    }
}
