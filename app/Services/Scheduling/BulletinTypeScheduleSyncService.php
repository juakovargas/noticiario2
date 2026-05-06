<?php

namespace App\Services\Scheduling;

use App\Models\BulletinType;
use App\Models\EditorialSchedule;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BulletinTypeScheduleSyncService
{
    public function __construct(private readonly EditorialScheduleRunner $runner)
    {
    }

    public function shouldHavePrimarySchedule(BulletinType $bulletinType): bool
    {
        return filled($bulletinType->default_schedule_time)
            || filled($bulletinType->default_run_time)
            || filled(data_get($bulletinType->metadata, 'schedule_times'))
            || filled($bulletinType->default_timezone)
            || filled($bulletinType->edition_type);
    }

    public function buildScheduleData(BulletinType $bulletinType, ?string $time = null, int $position = 0): array
    {
        $time ??= $bulletinType->default_run_time ?: $bulletinType->default_schedule_time;
        $frequency = $bulletinType->default_run_frequency ?: 'daily';
        $metadata = $bulletinType->metadata ?? [];
        $scheduleDate = data_get($metadata, 'schedule_date');
        $monthDay = data_get($metadata, 'month_day');
        $scheduleKey = $this->scheduleKey($bulletinType, $frequency, $time, $position);

        return [
            'name' => $bulletinType->name,
            'description' => $bulletinType->description,
            'location_id' => $bulletinType->location_id,
            'news_category_id' => $bulletinType->news_category_id,
            'language_id' => $bulletinType->language_id,
            'bulletin_type_id' => $bulletinType->id,
            'edition_type' => $bulletinType->edition_type ?: $this->editionTypeFromTime($time),
            'frequency_type' => $frequency,
            'run_frequency' => $frequency,
            'scheduled_time' => $time,
            'run_time' => $time,
            'scheduled_date' => $frequency === 'once' ? $scheduleDate : null,
            'timezone' => $bulletinType->default_timezone ?: config('app.timezone'),
            'run_days' => $bulletinType->default_run_days,
            'weekdays' => $bulletinType->default_run_days,
            'is_primary' => $position === 0,
            'auto_create_prompt_run' => true,
            'auto_generate_prompt' => true,
            'auto_run_pipeline' => (bool) $bulletinType->default_auto_run_pipeline,
            'auto_generate_ai_response' => (bool) $bulletinType->default_auto_generate_ai_response,
            'auto_create_script' => (bool) $bulletinType->default_auto_create_script,
            'auto_generate_metadata' => (bool) $bulletinType->default_auto_generate_metadata,
            'auto_extract_sources' => (bool) $bulletinType->default_auto_extract_sources,
            'metadata' => [
                ...$metadata,
                'bulletin_schedule_key' => $scheduleKey,
                'schedule_position' => $position,
                'month_day' => $frequency === 'monthly' ? $monthDay : null,
                'inferred_slot' => $this->slotFromTime($time),
            ],
        ];
    }

    public function syncSchedules(BulletinType $bulletinType): Collection
    {
        $desiredSchedules = $this->configuredTimes($bulletinType)
            ->map(fn (string $time, int $position) => $this->buildScheduleData($bulletinType, $time, $position));
        $existing = $bulletinType->schedules()->get();
        $claimedIds = collect();

        if ($desiredSchedules->isEmpty()) {
            $bulletinType->schedules()->get()->each(fn (EditorialSchedule $schedule) => $schedule->forceFill([
                'is_active' => false,
                'next_run_at' => null,
                'is_primary' => false,
                'metadata' => [
                    ...($schedule->metadata ?? []),
                    'obsolete_from_bulletin_config' => true,
                ],
            ])->save());

            return collect();
        }

        $synced = $desiredSchedules->map(function (array $data, int $position) use ($bulletinType, $existing, $claimedIds) {
            $schedule = $this->findMatchingSchedule($existing, $data, $claimedIds)
                ?? new EditorialSchedule([
                    'bulletin_type_id' => $bulletinType->id,
                    'slug' => $this->uniqueScheduleSlug($bulletinType, $data),
                ]);

            if ($schedule->exists) {
                $claimedIds->push($schedule->id);
            }

            $wasActive = $schedule->exists ? (bool) $schedule->is_active : null;
            $schedule->fill($data);
            $schedule->is_primary = $position === 0;
            $schedule->is_active = $wasActive ?? (bool) $bulletinType->default_schedule_is_active;
            $schedule->next_run_at = $schedule->is_active
                ? $this->runner->calculateNextRunAt($schedule)
                : null;
            $schedule->save();

            return $schedule->refresh();
        });

        $existing
            ->whereNotIn('id', $claimedIds->filter()->all())
            ->each(fn (EditorialSchedule $schedule) => $schedule->forceFill([
                'is_active' => false,
                'is_primary' => false,
                'next_run_at' => null,
                'metadata' => [
                    ...($schedule->metadata ?? []),
                    'obsolete_from_bulletin_config' => true,
                ],
            ])->save());

        return $synced->values();
    }

    public function syncPrimarySchedule(BulletinType $bulletinType): EditorialSchedule
    {
        return $this->syncSchedules($bulletinType)->first()
            ?? $bulletinType->primarySchedule()->first()
            ?? new EditorialSchedule(['bulletin_type_id' => $bulletinType->id, 'is_primary' => true]);
    }

    private function configuredTimes(BulletinType $bulletinType): Collection
    {
        $times = collect(data_get($bulletinType->metadata, 'schedule_times', []))
            ->push($bulletinType->default_run_time)
            ->push($bulletinType->default_schedule_time)
            ->filter()
            ->map(fn ($time) => substr((string) $time, 0, 5))
            ->filter(fn (string $time) => preg_match('/^\d{2}:\d{2}$/', $time))
            ->unique()
            ->sort()
            ->values();

        return $times;
    }

    private function findMatchingSchedule(Collection $existing, array $data, Collection $claimedIds): ?EditorialSchedule
    {
        $key = data_get($data, 'metadata.bulletin_schedule_key');
        $runDays = array_values($data['run_days'] ?? []);

        return $existing->first(function (EditorialSchedule $schedule) use ($key, $data, $runDays, $claimedIds) {
            if ($schedule->id && $claimedIds->contains($schedule->id)) {
                return false;
            }

            if ($key && data_get($schedule->metadata, 'bulletin_schedule_key') === $key) {
                return true;
            }

            return ($schedule->run_frequency ?: $schedule->frequency_type) === $data['run_frequency']
                && substr((string) ($schedule->run_time ?: $schedule->scheduled_time), 0, 5) === substr((string) $data['run_time'], 0, 5)
                && array_values($schedule->run_days ?? []) === $runDays
                && (int) data_get($schedule->metadata, 'month_day', 0) === (int) data_get($data, 'metadata.month_day', 0);
        });
    }

    private function scheduleKey(BulletinType $bulletinType, string $frequency, string $time, int $position): string
    {
        $days = implode('-', array_values($bulletinType->default_run_days ?? []));
        $monthDay = data_get($bulletinType->metadata, 'month_day', '');

        return sha1($bulletinType->id.'|'.$frequency.'|'.$time.'|'.$days.'|'.$monthDay.'|'.$position);
    }

    private function uniqueScheduleSlug(BulletinType $bulletinType, array $data): string
    {
        $base = Str::slug($bulletinType->slug.' '.$data['run_frequency'].' '.substr((string) $data['run_time'], 0, 5));
        $slug = $base;
        $counter = 2;

        while (EditorialSchedule::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function editionTypeFromTime(?string $time): string
    {
        return match ($this->slotFromTime($time)) {
            'afternoon', 'evening' => 'afternoon',
            'night', 'early_morning' => 'night',
            default => 'morning',
        };
    }

    private function slotFromTime(?string $time): string
    {
        $hour = (int) substr((string) ($time ?: '08:00'), 0, 2);

        return match (true) {
            $hour < 6 => 'early_morning',
            $hour < 12 => 'morning',
            $hour < 15 => 'midday',
            $hour < 18 => 'afternoon',
            $hour < 22 => 'evening',
            default => 'night',
        };
    }
}
