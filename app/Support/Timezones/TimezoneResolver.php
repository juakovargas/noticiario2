<?php

namespace App\Support\Timezones;

use DateTimeZone;

class TimezoneResolver
{
    public function resolve(?string $timezone, ?string $fallback = null): string
    {
        $normalized = $this->normalize($timezone);
        if ($this->isValid($normalized)) {
            return (string) $normalized;
        }

        $normalizedFallback = $this->normalize($fallback);
        if ($this->isValid($normalizedFallback)) {
            return (string) $normalizedFallback;
        }

        $appTimezone = $this->normalize((string) config('app.timezone'));
        if ($this->isValid($appTimezone)) {
            return (string) $appTimezone;
        }

        return 'Europe/Madrid';
    }

    public function normalize(?string $timezone): ?string
    {
        $value = trim((string) $timezone);
        if ($value === '') {
            return null;
        }

        return match (mb_strtolower($value)) {
            'madrid', 'españa', 'espana', 'spain' => 'Europe/Madrid',
            'france', 'paris' => 'Europe/Paris',
            'uk', 'london' => 'Europe/London',
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
