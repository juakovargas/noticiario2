<?php

namespace App\Services\Scheduling;

use App\Models\BulletinType;
use App\Models\EditorialSchedule;

class BulletinTypeScheduleSyncService
{
    public function __construct(private readonly EditorialScheduleRunner $runner)
    {
    }

    public function shouldHavePrimarySchedule(BulletinType $bulletinType): bool
    {
        return filled($bulletinType->default_schedule_time) || filled($bulletinType->default_timezone) || filled($bulletinType->edition_type);
    }

    public function buildScheduleData(BulletinType $bulletinType): array
    {
        return [
            'name' => $bulletinType->name,
            'description' => $bulletinType->description,
            'location_id' => $bulletinType->location_id,
            'news_category_id' => $bulletinType->news_category_id,
            'language_id' => $bulletinType->language_id,
            'bulletin_type_id' => $bulletinType->id,
            'edition_type' => $bulletinType->edition_type ?: 'morning',
            'run_frequency' => $bulletinType->default_run_frequency ?: 'daily',
            'run_time' => $bulletinType->default_run_time ?: $bulletinType->default_schedule_time,
            'timezone' => $bulletinType->default_timezone ?: config('app.timezone'),
            'run_days' => $bulletinType->default_run_days,
            'is_primary' => true,
            'auto_create_prompt_run' => true,
            'auto_generate_prompt' => true,
            'auto_run_pipeline' => (bool) $bulletinType->default_auto_run_pipeline,
            'auto_generate_ai_response' => (bool) $bulletinType->default_auto_generate_ai_response,
            'auto_create_script' => (bool) $bulletinType->default_auto_create_script,
            'auto_generate_metadata' => (bool) $bulletinType->default_auto_generate_metadata,
            'auto_extract_sources' => (bool) $bulletinType->default_auto_extract_sources,
        ];
    }

    public function syncPrimarySchedule(BulletinType $bulletinType): EditorialSchedule
    {
        $schedule = $bulletinType->primarySchedule()->first() ?? new EditorialSchedule([
            'bulletin_type_id' => $bulletinType->id,
            'is_primary' => true,
            'slug' => $bulletinType->slug.'-primary',
        ]);

        $isActive = $schedule->exists ? (bool) $schedule->is_active : (bool) $bulletinType->default_schedule_is_active;
        $schedule->fill($this->buildScheduleData($bulletinType));
        $schedule->is_active = $isActive;

        if (! $schedule->next_run_at && $schedule->is_active) {
            $schedule->next_run_at = $this->runner->calculateNextRunAt($schedule);
        }

        $schedule->save();

        return $schedule->refresh();
    }
}
