<?php

namespace Database\Seeders;

use App\Models\AiProvider;
use App\Models\BulletinType;
use App\Models\Language;
use App\Models\Location;
use App\Models\NewsCategory;
use App\Models\PromptProfile;
use App\Services\Scheduling\BulletinTypeScheduleSyncService;
use App\Services\Scheduling\EditorialScheduleRunner;
use Illuminate\Database\Seeder;

class SpainProductionBulletinsSeeder extends Seeder
{
    public function run(): void
    {
        $provider = $this->ensureGeminiGroundedProvider();
        $this->ensureDeepSeekProvider();
        $language = $this->ensureSpanishLanguage();
        $location = $this->ensureSpainLocation($language);
        $general = $this->ensureCategory('general', 'General', '#2563eb', 'newspaper');
        $sports = $this->ensureCategory('sports', 'Sports', '#16a34a', 'trophy');
        $technology = $this->ensureCategory('technology', 'Technology', '#7c3aed', 'cpu');
        $profile = PromptProfile::query()->where('slug', 'balanced-news')->first()
            ?: PromptProfile::query()->where('is_active', true)->where('is_default', true)->first();

        $definitions = [
            [
                'name' => 'Spain Morning General News',
                'slug' => 'spain-morning-general-news',
                'category_id' => $general->id,
                'edition_type' => 'morning',
                'time' => '08:00',
                'purpose' => 'morning general news briefing',
                'duration' => 75,
                'coverage_mode' => 'previous_period',
                'coverage_starts_offset_minutes' => -1440,
                'coverage_ends_offset_minutes' => -30,
                'include_future_agenda' => false,
                'min_news_items' => 4,
                'max_news_items' => 5,
            ],
            [
                'name' => 'Spain Midday Update',
                'slug' => 'spain-midday-update',
                'category_id' => $general->id,
                'edition_type' => 'afternoon',
                'time' => '15:00',
                'purpose' => 'midday update',
                'duration' => 60,
                'coverage_mode' => 'today_so_far',
                'coverage_starts_offset_minutes' => -330,
                'coverage_ends_offset_minutes' => -15,
                'include_future_agenda' => false,
                'min_news_items' => 4,
                'max_news_items' => 6,
            ],
            [
                'name' => 'Spain Evening Recap',
                'slug' => 'spain-evening-recap',
                'category_id' => $general->id,
                'edition_type' => 'night',
                'time' => '20:00',
                'purpose' => 'evening recap',
                'duration' => 75,
                'coverage_mode' => 'today_so_far',
                'coverage_starts_offset_minutes' => -720,
                'coverage_ends_offset_minutes' => -15,
                'include_future_agenda' => false,
                'min_news_items' => 5,
                'max_news_items' => 7,
            ],
            [
                'name' => 'Spain Sports Preview',
                'slug' => 'spain-sports-preview',
                'category_id' => $sports->id,
                'edition_type' => 'special',
                'time' => '19:00',
                'purpose' => 'sports agenda/preview for next 24 hours',
                'duration' => 75,
                'coverage_mode' => 'next_24_hours',
                'coverage_starts_offset_minutes' => 0,
                'coverage_ends_offset_minutes' => 1440,
                'include_future_agenda' => true,
                'min_news_items' => 4,
                'max_news_items' => 6,
            ],
        ];

        $syncService = app(BulletinTypeScheduleSyncService::class);
        $runner = app(EditorialScheduleRunner::class);

        foreach ($definitions as $index => $definition) {
            $bulletin = BulletinType::withTrashed()->firstOrNew(['slug' => $definition['slug']]);
            $bulletin->fill([
                'name' => $definition['name'],
                'description' => $definition['purpose'],
                'location_id' => $location->id,
                'news_category_id' => $definition['category_id'],
                'language_id' => $language->id,
                'default_prompt_profile_id' => $profile?->id,
                'preferred_ai_provider_id' => $provider->id,
                'edition_type' => $definition['edition_type'],
                'target_duration_seconds' => $definition['duration'],
                'default_schedule_time' => $definition['time'],
                'default_run_frequency' => 'daily',
                'default_run_time' => $definition['time'],
                'default_run_days' => null,
                'default_timezone' => 'Europe/Madrid',
                'default_schedule_is_active' => true,
                'default_auto_run_pipeline' => false,
                'default_auto_generate_ai_response' => false,
                'default_auto_create_script' => true,
                'default_auto_generate_metadata' => true,
                'default_auto_extract_sources' => true,
                'coverage_mode' => $definition['coverage_mode'],
                'coverage_starts_offset_minutes' => $definition['coverage_starts_offset_minutes'],
                'coverage_ends_offset_minutes' => $definition['coverage_ends_offset_minutes'],
                'coverage_description' => $definition['purpose'],
                'include_future_agenda' => $definition['include_future_agenda'],
                'include_historical_context' => true,
                'min_news_items' => $definition['min_news_items'],
                'max_news_items' => $definition['max_news_items'],
                'prompt_language' => 'es',
                'output_mode' => 'plain_final_script',
                'is_active' => true,
                'sort_order' => $index,
                'metadata' => [
                    'production_test' => true,
                    'country' => 'Spain',
                    'purpose' => $definition['purpose'],
                    'requires_current_news' => true,
                    'requires_grounded_news' => true,
                    'grounded_news_required' => true,
                    'provider_slug' => 'gemini-grounded',
                    'model' => 'gemini-3-flash-preview',
                    'auto_generate_ai_response' => false,
                    'ai_manual_approval_required' => true,
                ],
            ]);

            if ($bulletin->trashed()) {
                $bulletin->restore();
            }

            $bulletin->save();

            $schedule = $syncService->syncPrimarySchedule($bulletin->refresh());
            $schedule->forceFill([
                'is_active' => true,
                'auto_create_prompt_run' => true,
                'auto_generate_prompt' => true,
                'auto_run_pipeline' => false,
                'auto_generate_ai_response' => false,
                'auto_create_script' => true,
                'auto_generate_metadata' => true,
                'auto_extract_sources' => true,
            ]);
            $schedule->next_run_at = $runner->calculateNextRunAt($schedule);
            $schedule->save();
        }

        $this->seedFutureInactiveCandidates($provider, $profile, $general, $technology);
    }

    private function ensureGeminiGroundedProvider(): AiProvider
    {
        $provider = AiProvider::query()->firstOrNew(['slug' => 'gemini-grounded']);
        $provider->fill([
            'name' => 'Gemini Grounded',
            'provider_type' => 'gemini',
            'client_driver' => 'custom',
            'provider_category' => 'grounded_text',
            'capabilities' => ['script_generation', 'text_generation', 'news_grounding', 'google_search_grounding', 'citations'],
            'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
            'api_key_env_name' => 'GEMINI_API_KEY',
            'default_model' => 'gemini-3-flash-preview',
            'is_active' => true,
            'supports_grounding' => true,
            'supports_citations' => true,
            'supports_streaming' => false,
            'is_testing' => false,
            'is_local' => false,
            'timeout_seconds' => 90,
            'max_tokens' => 3000,
            'temperature' => 0.4,
            'requests_per_minute_limit' => 6,
            'requests_per_day_limit' => 1500,
            'tokens_per_minute_limit' => 1000000,
            'min_seconds_between_requests' => 12,
            'retry_on_rate_limit' => true,
            'max_retries' => 3,
            'initial_retry_delay_seconds' => 20,
            'max_retry_delay_seconds' => 300,
            'backoff_multiplier' => 2.0,
            'jitter_enabled' => true,
        ]);

        if (! $provider->exists) {
            $provider->is_default = false;
        }

        $provider->save();

        return $provider->refresh();
    }

    private function ensureDeepSeekProvider(): AiProvider
    {
        $provider = AiProvider::query()->firstOrNew(['slug' => 'deepseek-v4']);
        $provider->fill([
            'name' => 'DeepSeek',
            'provider_type' => 'text',
            'client_driver' => 'custom',
            'provider_category' => 'text',
            'capabilities' => ['script_generation', 'text_generation'],
            'base_url' => 'https://api.deepseek.com',
            'api_key_env_name' => 'DEEPSEEK_API_KEY',
            'default_model' => 'deepseek-v4-flash',
            'is_active' => true,
            'is_default' => false,
            'supports_grounding' => false,
            'supports_citations' => false,
            'supports_streaming' => false,
            'is_testing' => false,
            'is_local' => false,
            'timeout_seconds' => 90,
            'max_tokens' => 3000,
            'temperature' => 0.4,
            'requests_per_minute_limit' => 6,
            'requests_per_day_limit' => 1000,
            'min_seconds_between_requests' => 2,
            'retry_on_rate_limit' => true,
            'max_retries' => 3,
            'initial_retry_delay_seconds' => 3,
            'max_retry_delay_seconds' => 60,
            'backoff_multiplier' => 2.0,
            'jitter_enabled' => true,
        ]);
        $provider->save();

        return $provider->refresh();
    }

    private function ensureSpanishLanguage(): Language
    {
        return $this->ensureLanguage('es', 'Spanish', 'Espanol', 2);
    }

    private function ensureSpainLocation(Language $language): Location
    {
        return Location::query()->updateOrCreate(
            ['slug' => 'spain'],
            [
                'name' => 'Spain',
                'type' => 'country',
                'country_code' => 'ES',
                'timezone' => 'Europe/Madrid',
                'default_language_id' => $language->id,
                'latitude' => 40.4637,
                'longitude' => -3.7492,
                'map_zoom' => 5,
                'marker_color' => 'green',
                'marker_label' => 'ES',
                'show_on_map' => true,
                'is_active' => true,
                'sort_order' => 1,
            ],
        );
    }

    private function ensureCategory(string $slug, string $name, string $color, string $icon): NewsCategory
    {
        return NewsCategory::query()->updateOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'color' => $color, 'icon' => $icon, 'is_active' => true],
        );
    }

    private function ensureLanguage(string $code, string $name, string $nativeName, int $sortOrder): Language
    {
        return Language::query()->updateOrCreate(
            ['code' => $code],
            ['name' => $name, 'native_name' => $nativeName, 'is_active' => true, 'is_default' => false, 'sort_order' => $sortOrder],
        );
    }

    private function seedFutureInactiveCandidates(AiProvider $provider, ?PromptProfile $profile, NewsCategory $general, NewsCategory $technology): void
    {
        $languages = [
            'ca' => $this->ensureLanguage('ca', 'Catalan', 'Catala', 20),
            'gl' => $this->ensureLanguage('gl', 'Galician', 'Galego', 21),
            'eu' => $this->ensureLanguage('eu', 'Basque', 'Euskara', 22),
            'pt' => $this->ensureLanguage('pt', 'Portuguese', 'Portugues', 23),
            'fr' => $this->ensureLanguage('fr', 'French', 'Francais', 24),
            'en' => $this->ensureLanguage('en', 'English', 'English', 1),
        ];

        $locations = [
            'catalonia-spain' => $this->ensureLocation('catalonia-spain', 'Catalonia / Spain', 'region', 'ES', 'Europe/Madrid', $languages['ca'], 41.5912, 1.5209, 'CAT', 20),
            'galicia-spain' => $this->ensureLocation('galicia-spain', 'Galicia / Spain', 'region', 'ES', 'Europe/Madrid', $languages['gl'], 42.5751, -8.1339, 'GAL', 21),
            'basque-country-spain' => $this->ensureLocation('basque-country-spain', 'Basque Country / Spain', 'region', 'ES', 'Europe/Madrid', $languages['eu'], 42.9896, -2.6189, 'EUS', 22),
            'portugal' => $this->ensureLocation('portugal', 'Portugal', 'country', 'PT', 'Europe/Lisbon', $languages['pt'], 39.3999, -8.2245, 'PT', 23),
            'france' => $this->ensureLocation('france', 'France', 'country', 'FR', 'Europe/Paris', $languages['fr'], 46.2276, 2.2137, 'FR', 24),
            'global' => $this->ensureLocation('global', 'Global', 'global', null, 'UTC', $languages['en'], 0.0, 0.0, 'GLB', 30),
        ];

        $candidates = [
            ['name' => 'Catalonia Morning Briefing', 'slug' => 'catalonia-morning-briefing', 'location' => $locations['catalonia-spain'], 'language' => $languages['ca'], 'category' => $general, 'purpose' => 'inactive future Catalonia morning briefing'],
            ['name' => 'Galicia Morning Briefing', 'slug' => 'galicia-morning-briefing', 'location' => $locations['galicia-spain'], 'language' => $languages['gl'], 'category' => $general, 'purpose' => 'inactive future Galicia morning briefing'],
            ['name' => 'Basque Country Morning Briefing', 'slug' => 'basque-country-morning-briefing', 'location' => $locations['basque-country-spain'], 'language' => $languages['eu'], 'category' => $general, 'purpose' => 'inactive future Basque Country morning briefing'],
            ['name' => 'Portugal Morning Briefing', 'slug' => 'portugal-morning-briefing', 'location' => $locations['portugal'], 'language' => $languages['pt'], 'category' => $general, 'purpose' => 'inactive future Portugal morning briefing'],
            ['name' => 'France Morning Briefing', 'slug' => 'france-morning-briefing', 'location' => $locations['france'], 'language' => $languages['fr'], 'category' => $general, 'purpose' => 'inactive future France morning briefing'],
            ['name' => 'Global Technology Briefing', 'slug' => 'global-technology-briefing', 'location' => $locations['global'], 'language' => $languages['en'], 'category' => $technology, 'purpose' => 'inactive future global technology briefing'],
        ];

        $syncService = app(BulletinTypeScheduleSyncService::class);

        foreach ($candidates as $index => $candidate) {
            $bulletin = BulletinType::withTrashed()->firstOrNew(['slug' => $candidate['slug']]);
            $bulletin->fill([
                'name' => $candidate['name'],
                'description' => $candidate['purpose'],
                'location_id' => $candidate['location']->id,
                'news_category_id' => $candidate['category']->id,
                'language_id' => $candidate['language']->id,
                'default_prompt_profile_id' => $profile?->id,
                'preferred_ai_provider_id' => $provider->id,
                'edition_type' => 'morning',
                'target_duration_seconds' => 75,
                'default_schedule_time' => '08:00',
                'default_run_frequency' => 'daily',
                'default_run_time' => '08:00',
                'default_timezone' => $candidate['location']->timezone ?: 'UTC',
                'default_schedule_is_active' => false,
                'default_auto_run_pipeline' => false,
                'default_auto_generate_ai_response' => false,
                'default_auto_create_script' => true,
                'default_auto_generate_metadata' => true,
                'default_auto_extract_sources' => true,
                'coverage_mode' => 'previous_period',
                'coverage_starts_offset_minutes' => -1440,
                'coverage_ends_offset_minutes' => -30,
                'coverage_description' => $candidate['purpose'],
                'include_future_agenda' => false,
                'include_historical_context' => true,
                'min_news_items' => 4,
                'max_news_items' => 5,
                'prompt_language' => in_array($candidate['language']->code, ['en', 'fr'], true) ? $candidate['language']->code : 'es',
                'output_mode' => 'plain_final_script',
                'is_active' => false,
                'sort_order' => 100 + $index,
                'metadata' => [
                    'future_candidate' => true,
                    'purpose' => $candidate['purpose'],
                    'requires_current_news' => true,
                    'requires_grounded_news' => true,
                    'grounded_news_required' => true,
                    'provider_slug' => 'gemini-grounded',
                    'model' => 'gemini-3-flash-preview',
                    'auto_generate_ai_response' => false,
                    'ai_manual_approval_required' => true,
                ],
            ]);

            if ($bulletin->trashed()) {
                $bulletin->restore();
            }

            $bulletin->save();

            $schedule = $syncService->syncPrimarySchedule($bulletin->refresh());
            $schedule->forceFill([
                'is_active' => false,
                'auto_create_prompt_run' => true,
                'auto_generate_prompt' => true,
                'auto_run_pipeline' => false,
                'auto_generate_ai_response' => false,
                'auto_create_script' => true,
                'auto_generate_metadata' => true,
                'auto_extract_sources' => true,
                'next_run_at' => null,
            ])->save();
        }
    }

    private function ensureLocation(string $slug, string $name, string $type, ?string $countryCode, string $timezone, Language $language, float $latitude, float $longitude, string $markerLabel, int $sortOrder): Location
    {
        return Location::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'type' => $type,
                'country_code' => $countryCode,
                'timezone' => $timezone,
                'default_language_id' => $language->id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'map_zoom' => $type === 'global' ? 1 : 5,
                'marker_color' => 'slate',
                'marker_label' => $markerLabel,
                'show_on_map' => true,
                'is_active' => true,
                'sort_order' => $sortOrder,
            ],
        );
    }
}
