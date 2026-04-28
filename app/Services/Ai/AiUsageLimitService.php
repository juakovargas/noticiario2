<?php

namespace App\Services\Ai;

use App\Models\AiProvider;
use App\Models\AiRequestLog;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AiUsageLimitService
{
    /**
     * @return array<string,mixed>
     */
    public function checkProviderLimits(AiProvider $provider): array
    {
        $summary = $this->getProviderUsageSummary($provider);
        $warnings = [];
        $blocked = false;

        if ($summary['daily_request_limit'] !== null && $summary['daily_requests'] >= $summary['daily_request_limit']) {
            $warnings[] = 'AI provider daily limit reached.';
            $blocked = true;
        }

        if ($summary['monthly_request_limit'] !== null && $summary['monthly_requests'] >= $summary['monthly_request_limit']) {
            $warnings[] = 'AI provider monthly limit reached.';
            $blocked = true;
        }

        if ($summary['daily_cost_limit'] !== null && $summary['daily_estimated_cost'] >= $summary['daily_cost_limit']) {
            $warnings[] = 'AI provider daily cost limit reached.';
            $blocked = true;
        }

        if ($summary['monthly_cost_limit'] !== null && $summary['monthly_estimated_cost'] >= $summary['monthly_cost_limit']) {
            $warnings[] = 'AI provider monthly cost limit reached.';
            $blocked = true;
        }

        return [
            ...$summary,
            'warnings' => $warnings,
            'blocked' => $blocked,
        ];
    }

    public function assertProviderCanRun(AiProvider $provider): void
    {
        $result = $this->checkProviderLimits($provider);

        if ($result['blocked'] !== true) {
            return;
        }

        throw ValidationException::withMessages([
            'ai_provider_id' => $result['warnings'][0] ?? 'AI provider daily limit reached.',
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    public function getProviderUsageSummary(AiProvider $provider): array
    {
        $todayStart = Carbon::now()->startOfDay();
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $dailySuccess = AiRequestLog::query()
            ->where('ai_provider_id', $provider->id)
            ->where('status', 'success')
            ->whereBetween('created_at', [$todayStart, Carbon::now()]);

        $monthlySuccess = AiRequestLog::query()
            ->where('ai_provider_id', $provider->id)
            ->where('status', 'success')
            ->whereBetween('created_at', [$monthStart, $monthEnd]);

        return [
            'daily_requests' => (int) (clone $dailySuccess)->count(),
            'monthly_requests' => (int) (clone $monthlySuccess)->count(),
            'daily_estimated_cost' => round((float) (clone $dailySuccess)->whereNotNull('estimated_cost')->sum('estimated_cost'), 6),
            'monthly_estimated_cost' => round((float) (clone $monthlySuccess)->whereNotNull('estimated_cost')->sum('estimated_cost'), 6),
            'daily_request_limit' => $provider->daily_request_limit,
            'monthly_request_limit' => $provider->monthly_request_limit,
            'daily_cost_limit' => $provider->daily_cost_limit,
            'monthly_cost_limit' => $provider->monthly_cost_limit,
        ];
    }
}
