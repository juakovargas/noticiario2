<?php

namespace Tests\Unit;

use App\Models\AiProvider;
use App\Models\AiRequestLog;
use App\Services\Ai\AiUsageLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiUsageLimitServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_allows_provider_under_limits(): void
    {
        $provider = AiProvider::factory()->create(['daily_request_limit' => 5]);
        AiRequestLog::query()->create(['ai_provider_id' => $provider->id, 'status' => 'success', 'created_at' => now()]);

        $result = app(AiUsageLimitService::class)->checkProviderLimits($provider);

        $this->assertFalse($result['blocked']);
    }

    public function test_blocks_daily_and_monthly_request_limits_and_cost_limits(): void
    {
        $provider = AiProvider::factory()->create([
            'daily_request_limit' => 1,
            'monthly_request_limit' => 1,
            'daily_cost_limit' => 1,
            'monthly_cost_limit' => 1,
        ]);

        AiRequestLog::query()->create(['ai_provider_id' => $provider->id, 'status' => 'success', 'estimated_cost' => 2, 'created_at' => now()]);

        $result = app(AiUsageLimitService::class)->checkProviderLimits($provider);

        $this->assertTrue($result['blocked']);
        $this->assertNotEmpty($result['warnings']);
    }

    public function test_uses_current_day_and_month_windows(): void
    {
        $provider = AiProvider::factory()->create(['daily_request_limit' => 2, 'monthly_request_limit' => 3]);

        AiRequestLog::query()->create(['ai_provider_id' => $provider->id, 'status' => 'success', 'created_at' => now()]);
        AiRequestLog::query()->create(['ai_provider_id' => $provider->id, 'status' => 'success', 'created_at' => now()->subMonth()->startOfMonth()]);

        $summary = app(AiUsageLimitService::class)->getProviderUsageSummary($provider);

        $this->assertSame(1, $summary['daily_requests']);
        $this->assertSame(1, $summary['monthly_requests']);
    }
}
