<?php

namespace Tests\Unit;

use App\Models\BulletinPromptRun;
use App\Models\BulletinType;
use App\Models\Language;
use App\Models\Location;
use App\Models\NewsCategory;
use App\Models\PromptProfile;
use App\Services\PromptGeneration\BulletinPromptGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BulletinPromptGeneratorTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function generated_prompt_includes_timing_coverage_count_and_quality_rules(): void
    {
        $location = Location::factory()->create(['name' => 'Spain']);
        $category = NewsCategory::factory()->create(['name' => 'General']);
        $language = Language::factory()->create(['name' => 'Spanish', 'code' => 'es']);
        $profile = PromptProfile::query()->create(['name' => 'Balanced', 'slug' => 'balanced', 'is_active' => true]);

        $type = BulletinType::query()->create([
            'name' => 'Morning',
            'slug' => 'morning',
            'location_id' => $location->id,
            'news_category_id' => $category->id,
            'language_id' => $language->id,
            'default_prompt_profile_id' => $profile->id,
            'default_timezone' => 'Europe/Madrid',
            'coverage_mode' => 'previous_period',
            'coverage_starts_offset_minutes' => -1440,
            'coverage_ends_offset_minutes' => -30,
            'min_news_items' => 4,
            'max_news_items' => 5,
            'prompt_language' => 'es',
            'output_mode' => 'structured_script',
            'include_future_agenda' => false,
        ]);

        $run = BulletinPromptRun::query()->create([
            'bulletin_type_id' => $type->id,
            'prompt_profile_id' => $profile->id,
            'title' => 'Run',
            'scheduled_for' => '2026-04-29 06:00:00',
        ]);

        $prompt = app(BulletinPromptGenerator::class)->generate($run);

        $this->assertStringContainsString('Broadcast date: 2026-04-29', $prompt);
        $this->assertStringContainsString('Broadcast time: 08:00', $prompt);
        $this->assertStringContainsString('Timezone: Europe/Madrid', $prompt);
        $this->assertStringContainsString('Desde: 2026-04-28 08:00 Europe/Madrid', $prompt);
        $this->assertStringContainsString('Hasta: 2026-04-29 07:30 Europe/Madrid', $prompt);
        $this->assertStringNotContainsString('Scheduled date/time: N/A', $prompt);
        $this->assertStringContainsString('Minimum news items: 4', $prompt);
        $this->assertStringContainsString('Maximum news items: 5', $prompt);
        $this->assertStringContainsString('OUTPUT MODE: structured_script', $prompt);
        $this->assertStringContainsString('No inventes datos ni hechos', $prompt);
        $this->assertStringContainsString('No incluir agenda futura', $prompt);
    }

    #[Test]
    public function plain_script_mode_and_fallback_counts_are_included(): void
    {
        $type = BulletinType::query()->create([
            'name' => 'English',
            'slug' => 'english',
            'coverage_mode' => 'last_24_hours',
            'target_duration_seconds' => 55,
            'prompt_language' => 'en',
            'output_mode' => 'plain_script',
            'default_timezone' => 'UTC',
        ]);

        $run = BulletinPromptRun::query()->create([
            'bulletin_type_id' => $type->id,
            'title' => 'Run',
            'scheduled_for' => '2026-04-29 10:00:00',
        ]);

        $prompt = app(BulletinPromptGenerator::class)->generate($run);

        $this->assertStringContainsString('Minimum news items: 3', $prompt);
        $this->assertStringContainsString('Maximum news items: 4', $prompt);
        $this->assertStringContainsString('OUTPUT MODE: plain_script', $prompt);
        $this->assertStringContainsString('Do not invent facts.', $prompt);
    }
}
