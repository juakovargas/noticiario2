<?php

namespace Tests\Unit;

use App\Models\BulletinPromptRun;
use App\Models\BulletinType;
use App\Services\PromptGeneration\BulletinCoverageWindowResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BulletinCoverageWindowResolverTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_resolves_previous_period_for_morning_run(): void
    {
        $type = BulletinType::query()->create([
            'name' => 'Morning',
            'slug' => 'morning',
            'coverage_mode' => 'previous_period',
            'coverage_starts_offset_minutes' => -1440,
            'coverage_ends_offset_minutes' => -30,
            'default_timezone' => 'Europe/Madrid',
        ]);

        $run = BulletinPromptRun::query()->create([
            'bulletin_type_id' => $type->id,
            'title' => 'Run',
            'scheduled_for' => '2026-04-29 06:00:00',
        ]);

        $resolved = app(BulletinCoverageWindowResolver::class)->resolve($run);

        $this->assertSame('2026-04-28 08:00', $resolved['coverage_from']?->format('Y-m-d H:i'));
        $this->assertSame('2026-04-29 07:30', $resolved['coverage_to']?->format('Y-m-d H:i'));
    }

    #[Test]
    public function it_resolves_afternoon_window_using_offsets(): void
    {
        $type = BulletinType::query()->create([
            'name' => 'Afternoon',
            'slug' => 'afternoon',
            'coverage_mode' => 'previous_period',
            'coverage_starts_offset_minutes' => -420,
            'coverage_ends_offset_minutes' => -30,
            'default_timezone' => 'Europe/Madrid',
        ]);

        $run = BulletinPromptRun::query()->create([
            'bulletin_type_id' => $type->id,
            'title' => 'Run',
            'scheduled_for' => '2026-04-29 13:00:00',
        ]);

        $resolved = app(BulletinCoverageWindowResolver::class)->resolve($run);

        $this->assertSame('2026-04-29 08:00', $resolved['coverage_from']?->format('Y-m-d H:i'));
        $this->assertSame('2026-04-29 14:30', $resolved['coverage_to']?->format('Y-m-d H:i'));
    }

    #[Test]
    public function it_resolves_today_so_far_last_and_next_24_hours_and_null_schedule(): void
    {
        $scheduled = '2026-04-29 10:15:00';
        $type = BulletinType::query()->create(['name' => 'Type', 'slug' => 'type', 'default_timezone' => 'Europe/Madrid']);

        $todayRun = BulletinPromptRun::query()->create(['bulletin_type_id' => $type->id, 'title' => 'Today', 'scheduled_for' => $scheduled]);
        $type->update(['coverage_mode' => 'today_so_far']);
        $today = app(BulletinCoverageWindowResolver::class)->resolve($todayRun->fresh());
        $this->assertSame('2026-04-29 00:00', $today['coverage_from']?->format('Y-m-d H:i'));
        $this->assertSame('2026-04-29 12:15', $today['coverage_to']?->format('Y-m-d H:i'));

        $type->update(['coverage_mode' => 'last_24_hours']);
        $last = app(BulletinCoverageWindowResolver::class)->resolve($todayRun->fresh());
        $this->assertSame('2026-04-28 12:15', $last['coverage_from']?->format('Y-m-d H:i'));
        $this->assertSame('2026-04-29 12:15', $last['coverage_to']?->format('Y-m-d H:i'));

        $type->update(['coverage_mode' => 'next_24_hours']);
        $next = app(BulletinCoverageWindowResolver::class)->resolve($todayRun->fresh());
        $this->assertSame('2026-04-29 12:15', $next['coverage_from']?->format('Y-m-d H:i'));
        $this->assertSame('2026-04-30 12:15', $next['coverage_to']?->format('Y-m-d H:i'));

        $nullRun = BulletinPromptRun::query()->create(['bulletin_type_id' => $type->id, 'title' => 'Null']);
        $resolvedNull = app(BulletinCoverageWindowResolver::class)->resolve($nullRun);
        $this->assertNotNull($resolvedNull['scheduled_for']);
    }
}
