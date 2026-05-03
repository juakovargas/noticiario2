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
    public function generated_spanish_prompt_is_consistent_and_explains_script_usage(): void
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

        $this->assertStringContainsString('FECHA Y HORA DE EMISIÓN', $prompt);
        $this->assertStringContainsString('Fecha de emisión: 2026-04-29', $prompt);
        $this->assertStringContainsString('Hora de emisión: 08:00', $prompt);
        $this->assertStringContainsString('Zona horaria: Europe/Madrid', $prompt);
        $this->assertStringContainsString('Mínimo de noticias: 4', $prompt);
        $this->assertStringContainsString('Máximo de noticias: 5', $prompt);
        $this->assertStringContainsString('REGLAS DE SELECCIÓN DE NOTICIAS', $prompt);
        $this->assertStringContainsString('NÚMERO DE NOTICIAS', $prompt);
        $this->assertStringContainsString('MODO DE SALIDA: structured_script', $prompt);
        $this->assertStringContainsString('Los bloques SCRIPT son la narración principal', $prompt);
        $this->assertStringContainsString('Incluye al menos una pista de fuente por noticia', $prompt);
        $this->assertStringNotContainsString('BROADCAST TIMING', $prompt);
        $this->assertStringNotContainsString('NEWS SELECTION RULES', $prompt);
    }

    #[Test]
    public function english_plain_prompt_includes_source_hint_requirements(): void
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

        $this->assertStringContainsString('Choose 3 to 4 relevant items', $prompt);
        $this->assertStringContainsString('Prioritize reliable, source-aware factuality', $prompt);
        $this->assertStringContainsString('Do not invent facts, figures, quotes, or sources.', $prompt);
        $this->assertStringNotContainsString('OUTPUT MODE:', $prompt);
        $this->assertStringNotContainsString('STRUCTURE:', $prompt);
        $this->assertStringNotContainsString('TITLE:', $prompt);
    }
}
