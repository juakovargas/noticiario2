<?php

namespace App\Services\Ai;

use App\Models\AiProvider;
use Carbon\CarbonInterface;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;

class AiProviderRateLimiter
{
    public function canCall(AiProvider $provider): bool
    {
        return $this->secondsUntilAvailable($provider) <= 0;
    }

    public function secondsUntilAvailable(AiProvider $provider): int
    {
        $now = now();
        $until = $provider->rate_limited_until;
        $seconds = 0;

        if ($until instanceof CarbonInterface && $until->isFuture()) {
            $seconds = max($seconds, $now->diffInSeconds($until));
        }

        if ($provider->last_request_at instanceof CarbonInterface && $provider->min_seconds_between_requests) {
            $next = $provider->last_request_at->copy()->addSeconds((int) $provider->min_seconds_between_requests);
            if ($next->isFuture()) {
                $seconds = max($seconds, $now->diffInSeconds($next));
            }
        }

        return $seconds;
    }

    public function markRequestStarted(AiProvider $provider): void
    {
        $provider->forceFill(['last_request_at' => now()])->save();
    }

    public function markRequestFinished(AiProvider $provider): void
    {
        $provider->forceFill(['last_request_at' => now()])->save();
    }

    public function markRateLimited(AiProvider $provider, ?int $retryAfterSeconds = null, array $metadata = []): void
    {
        $wait = max(1, $retryAfterSeconds ?? $provider->initial_retry_delay_seconds ?? 5);
        $provider->forceFill([
            'rate_limited_until' => now()->addSeconds($wait),
            'rate_limit_metadata' => $metadata,
        ])->save();
    }

    public function calculateBackoffDelay(AiProvider $provider, int $attempt): int
    {
        $initial = max(1, (int) ($provider->initial_retry_delay_seconds ?? 5));
        $max = max($initial, (int) ($provider->max_retry_delay_seconds ?? 300));
        $multiplier = max(1, (float) ($provider->backoff_multiplier ?? 2));
        $delay = (int) min($max, round($initial * ($multiplier ** max(0, $attempt - 1))));

        if ($provider->jitter_enabled) {
            $jitter = random_int(0, max(1, (int) floor($delay * 0.25)));
            $delay = min($max, $delay + $jitter);
        }

        return $delay;
    }

    public function isRateLimitStatus(int $statusCode): bool
    {
        return $statusCode === 429;
    }

    public function parseRetryAfterSeconds(Response $response): ?int
    {
        $retryAfter = $response->header('Retry-After');
        if (blank($retryAfter)) {
            return null;
        }

        if (is_numeric($retryAfter)) {
            return max(1, (int) $retryAfter);
        }

        try {
            return max(1, now()->diffInSeconds(Carbon::parse((string) $retryAfter), false));
        } catch (\Throwable) {
            return null;
        }
    }
}
