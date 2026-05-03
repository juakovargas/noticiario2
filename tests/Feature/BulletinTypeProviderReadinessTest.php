<?php

namespace Tests\Feature;

use App\Models\AiProvider;
use App\Models\BulletinType;
use Database\Seeders\SpainProductionBulletinsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class BulletinTypeProviderReadinessTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    public function test_bulletin_type_uses_preferred_ai_provider_relationship(): void
    {
        $provider = AiProvider::factory()->create(['name' => 'Gemini Grounded', 'default_model' => 'gemini-3-flash-preview']);
        $bulletin = BulletinType::factory()->create(['preferred_ai_provider_id' => $provider->id]);

        $this->assertTrue($bulletin->fresh()->preferredAiProvider->is($provider));
    }

    public function test_bulletin_type_index_includes_provider_and_model(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);
        $provider = AiProvider::factory()->create([
            'name' => 'Gemini Grounded',
            'default_model' => 'gemini-3-flash-preview',
            'supports_grounding' => true,
            'provider_category' => 'grounded_text',
        ]);

        BulletinType::factory()->create([
            'name' => 'Spain Morning General News',
            'preferred_ai_provider_id' => $provider->id,
        ]);

        $this->actingAs($editor)
            ->get(route('editor.bulletin-types.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Editor/BulletinTypes/Index')
                ->where('bulletinTypes.data.0.preferred_ai_provider.name', 'Gemini Grounded')
                ->where('bulletinTypes.data.0.preferred_ai_provider.default_model', 'gemini-3-flash-preview')
            );
    }

    public function test_spain_production_bulletins_seeder_is_idempotent_and_assigns_gemini_grounded(): void
    {
        $this->seed(SpainProductionBulletinsSeeder::class);
        $this->seed(SpainProductionBulletinsSeeder::class);

        $provider = AiProvider::query()->where('slug', 'gemini-grounded')->firstOrFail();
        $deepSeek = AiProvider::query()->where('slug', 'deepseek-v4')->firstOrFail();
        $slugs = [
            'spain-morning-general-news',
            'spain-midday-update',
            'spain-evening-recap',
            'spain-sports-preview',
        ];

        $this->assertSame(4, BulletinType::query()->whereIn('slug', $slugs)->count());

        foreach ($slugs as $slug) {
            $bulletin = BulletinType::query()->where('slug', $slug)->firstOrFail();
            $this->assertSame($provider->id, $bulletin->preferred_ai_provider_id);
            $this->assertSame('plain_final_script', $bulletin->output_mode);
            $this->assertTrue((bool) $bulletin->primarySchedule()->first()?->is_active);
            $this->assertFalse((bool) $bulletin->primarySchedule()->first()?->auto_generate_ai_response);
        }

        $midday = BulletinType::query()->where('slug', 'spain-midday-update')->firstOrFail();
        $this->assertSame('15:00', substr((string) $midday->primarySchedule()->first()?->run_time, 0, 5));
        $this->assertSame('DeepSeek', $deepSeek->name);
        $this->assertSame('text', $deepSeek->provider_type);
        $this->assertSame('DEEPSEEK_API_KEY', $deepSeek->api_key_env_name);

        foreach ([
            'catalonia-morning-briefing',
            'galicia-morning-briefing',
            'basque-country-morning-briefing',
            'portugal-morning-briefing',
            'france-morning-briefing',
            'global-technology-briefing',
        ] as $futureSlug) {
            $futureBulletin = BulletinType::query()->where('slug', $futureSlug)->firstOrFail();
            $this->assertFalse((bool) $futureBulletin->is_active);
            $this->assertFalse((bool) $futureBulletin->primarySchedule()->first()?->is_active);
            $this->assertSame($provider->id, $futureBulletin->preferred_ai_provider_id);
        }
    }
}
