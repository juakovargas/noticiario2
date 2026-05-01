<?php

namespace App\Support\Timezones;

use DateTimeZone;

class TimezoneResolver
{
    /** @param array<int, string|null> $candidates */
    public function resolve(array $candidates = []): string
    {
        $fallbacks = [config('app.timezone'), 'Europe/Madrid'];

        foreach (array_merge($candidates, $fallbacks) as $candidate) {
            $normalized = $this->normalize($candidate);
            if ($normalized !== null && $this->isValid($normalized)) {
                return $normalized;
            }
        }

        return 'Europe/Madrid';
    }

    public function normalize(?string $timezone): ?string
    {
        $value = trim((string) $timezone);
        if ($value === '') {
            return null;
        }

        $key = mb_strtolower($value);
        return match ($key) {
            'madrid', 'spain', 'españa', 'espana' => 'Europe/Madrid',
            'paris', 'france' => 'Europe/Paris',
            'london', 'uk' => 'Europe/London',
            'utc' => 'UTC',
            default => $value,
        };
    }

    public function isValid(?string $timezone): bool
    {
        if (! is_string($timezone) || trim($timezone) === '') {
            return false;
        }

        try {
            new DateTimeZone($timezone);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
