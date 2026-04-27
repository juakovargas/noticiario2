<?php

namespace App\Services\PromptGeneration;

use App\Models\BulletinPromptRun;
use Carbon\Carbon;

class BulletinCoverageWindowResolver
{
    /**
     * @return array{scheduled_for: Carbon, timezone: string, coverage_from: Carbon|null, coverage_to: Carbon|null, coverage_mode: string, description: string|null}
     */
    public function resolve(BulletinPromptRun $run): array
    {
        $run->loadMissing('bulletinType');
        $type = $run->bulletinType;

        $timezone = (string) ($type?->default_timezone ?: config('app.timezone'));
        $scheduledFor = ($run->scheduled_for ?: now())->copy()->timezone($timezone);

        $mode = (string) ($type?->coverage_mode ?: 'previous_period');
        $startOffset = is_numeric($type?->coverage_starts_offset_minutes) ? (int) $type->coverage_starts_offset_minutes : null;
        $endOffset = is_numeric($type?->coverage_ends_offset_minutes) ? (int) $type->coverage_ends_offset_minutes : null;

        $coverageFrom = null;
        $coverageTo = null;

        switch ($mode) {
            case 'today_so_far':
                $coverageFrom = $scheduledFor->copy()->startOfDay();
                $coverageTo = $scheduledFor->copy();
                break;
            case 'yesterday':
                $coverageFrom = $scheduledFor->copy()->subDay()->startOfDay();
                $coverageTo = $scheduledFor->copy()->subDay()->endOfDay();
                break;
            case 'last_24_hours':
                $coverageFrom = $scheduledFor->copy()->subHours(24);
                $coverageTo = $scheduledFor->copy();
                break;
            case 'next_24_hours':
                $coverageFrom = $scheduledFor->copy();
                $coverageTo = $scheduledFor->copy()->addHours(24);
                break;
            case 'custom':
            case 'previous_period':
                if ($startOffset !== null) {
                    $coverageFrom = $scheduledFor->copy()->addMinutes($startOffset);
                }
                if ($endOffset !== null) {
                    $coverageTo = $scheduledFor->copy()->addMinutes($endOffset);
                }
                break;
            case 'none':
            default:
                break;
        }

        if ($coverageFrom && $coverageTo && $coverageFrom->greaterThan($coverageTo)) {
            [$coverageFrom, $coverageTo] = [$coverageTo, $coverageFrom];
        }

        return [
            'scheduled_for' => $scheduledFor,
            'timezone' => $timezone,
            'coverage_from' => $coverageFrom,
            'coverage_to' => $coverageTo,
            'coverage_mode' => $mode,
            'description' => $type?->coverage_description,
        ];
    }
}
