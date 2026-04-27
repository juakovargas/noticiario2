<?php

namespace Database\Seeders;

use App\Models\Edition;
use App\Models\EditorialRequest;
use App\Models\AiPromptTemplate;
use App\Models\AiProvider;
use App\Models\EditorialTemplate;
use App\Models\Language;
use App\Models\Location;
use App\Models\NewsCategory;
use App\Models\NewsItem;
use App\Models\NewsSource;
use App\Models\NewsCategoryTranslation;
use App\Models\Script;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DemoEditorialSeeder extends Seeder
{
    public function run(): void
    {
        $locations = $this->seedLocations();
        $categories = $this->seedCategories();
        $aiProviders = $this->seedAiProviders();
        $aiPromptTemplates = $this->seedAiPromptTemplates($locations, $categories);
        $sources = $this->seedSources($categories, $locations);
        $newsItems = $this->seedNewsItems($locations, $categories, $sources);
        $editions = $this->seedEditions($locations);

        $this->attachNewsItemsToEditions($editions, $newsItems);
        $this->seedScripts($editions);
        $this->seedEditorialTemplates($locations);
        $this->seedEditorialRequests($locations, $categories, $aiProviders, $aiPromptTemplates);
    }

    /**
     * @return array<string, Location>
     */
    private function seedLocations(): array
    {
        $languages = Language::query()->whereIn('code', ['en', 'es', 'fr'])->get()->keyBy('code');
        $global = $this->upsertLocation('Global', [
            'type' => 'global',
            'country_code' => null,
            'timezone' => 'UTC',
            'sort_order' => 0,
            'default_language_id' => $languages->get('en')?->id,
        ]);

        $spain = $this->upsertLocation('Spain', [
            'parent_id' => $global->id,
            'type' => 'country',
            'country_code' => 'ES',
            'timezone' => 'Europe/Madrid',
            'sort_order' => 10,
            'default_language_id' => $languages->get('es')?->id,
        ]);

        $france = $this->upsertLocation('France', [
            'parent_id' => $global->id,
            'type' => 'country',
            'country_code' => 'FR',
            'timezone' => 'Europe/Paris',
            'sort_order' => 20,
            'default_language_id' => $languages->get('fr')?->id,
        ]);

        $us = $this->upsertLocation('United States', [
            'parent_id' => $global->id,
            'type' => 'country',
            'country_code' => 'US',
            'timezone' => 'America/New_York',
            'sort_order' => 30,
            'default_language_id' => $languages->get('en')?->id,
        ]);

        $madrid = $this->upsertLocation('Madrid', [
            'parent_id' => $spain->id,
            'type' => 'region',
            'country_code' => 'ES',
            'timezone' => 'Europe/Madrid',
            'sort_order' => 11,
            'default_language_id' => $languages->get('es')?->id,
        ]);

        $barcelona = $this->upsertLocation('Barcelona', [
            'parent_id' => $spain->id,
            'type' => 'city',
            'country_code' => 'ES',
            'timezone' => 'Europe/Madrid',
            'sort_order' => 12,
            'default_language_id' => $languages->get('es')?->id,
        ]);

        $valencia = $this->upsertLocation('Valencia', [
            'parent_id' => $spain->id,
            'type' => 'city',
            'country_code' => 'ES',
            'timezone' => 'Europe/Madrid',
            'sort_order' => 13,
            'default_language_id' => $languages->get('es')?->id,
        ]);

        $sevilla = $this->upsertLocation('Sevilla', [
            'parent_id' => $spain->id,
            'type' => 'city',
            'country_code' => 'ES',
            'timezone' => 'Europe/Madrid',
            'sort_order' => 14,
            'default_language_id' => $languages->get('es')?->id,
        ]);

        $paris = $this->upsertLocation('Paris', [
            'parent_id' => $france->id,
            'type' => 'city',
            'country_code' => 'FR',
            'timezone' => 'Europe/Paris',
            'sort_order' => 21,
            'default_language_id' => $languages->get('fr')?->id,
        ]);

        $lyon = $this->upsertLocation('Lyon', [
            'parent_id' => $france->id,
            'type' => 'city',
            'country_code' => 'FR',
            'timezone' => 'Europe/Paris',
            'sort_order' => 22,
            'default_language_id' => $languages->get('fr')?->id,
        ]);

        return compact('global', 'spain', 'france', 'us', 'madrid', 'barcelona', 'valencia', 'sevilla', 'paris', 'lyon');
    }

    private function upsertLocation(string $name, array $attributes): Location
    {
        return Location::query()->updateOrCreate(
            ['slug' => Str::slug($name)],
            array_merge([
                'name' => $name,
                'is_active' => true,
            ], $attributes),
        );
    }

    /**
     * @return array<string, NewsCategory>
     */
    private function seedCategories(): array
    {
        $definitions = [
            ['name' => 'General', 'description' => 'General editorial updates and broad coverage.', 'color' => '#3b82f6', 'icon' => 'newspaper', 'sort_order' => 1],
            ['name' => 'Politics', 'description' => 'Institutional decisions, policy updates, and civic agenda.', 'color' => '#ef4444', 'icon' => 'landmark', 'sort_order' => 2],
            ['name' => 'Economy', 'description' => 'Markets, inflation signals, and business environment outlook.', 'color' => '#f59e0b', 'icon' => 'chart-line', 'sort_order' => 3],
            ['name' => 'Sports', 'description' => 'Traditional sports coverage with local and international relevance.', 'color' => '#22c55e', 'icon' => 'trophy', 'sort_order' => 4],
            ['name' => 'Culture', 'description' => 'Arts, museums, festivals, and creative industry news.', 'color' => '#ec4899', 'icon' => 'palette', 'sort_order' => 5],
            ['name' => 'Science', 'description' => 'Research highlights and scientific community milestones.', 'color' => '#0ea5e9', 'icon' => 'flask', 'sort_order' => 6],
            ['name' => 'Technology', 'description' => 'Digital transformation, AI, and product ecosystem shifts.', 'color' => '#6366f1', 'icon' => 'cpu', 'sort_order' => 7],
            ['name' => 'Esports', 'description' => 'Competitive gaming events, leagues, and player stories.', 'color' => '#a855f7', 'icon' => 'gamepad', 'sort_order' => 8],
            ['name' => 'Society', 'description' => 'Community initiatives and social impact reporting.', 'color' => '#14b8a6', 'icon' => 'users', 'sort_order' => 9],
            ['name' => 'Health', 'description' => 'Public health campaigns and prevention recommendations.', 'color' => '#f43f5e', 'icon' => 'heart-pulse', 'sort_order' => 10],
            ['name' => 'Climate', 'description' => 'Weather risks, climate adaptation, and sustainability actions.', 'color' => '#06b6d4', 'icon' => 'cloud-sun', 'sort_order' => 11],
            ['name' => 'International', 'description' => 'Cross-border developments shaping regional decisions.', 'color' => '#334155', 'icon' => 'globe', 'sort_order' => 12],
        ];

        $categories = [];

        foreach ($definitions as $definition) {
            $slug = Str::slug($definition['name']);

            $categories[$slug] = NewsCategory::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'color' => $definition['color'],
                    'icon' => $definition['icon'],
                    'is_active' => true,
                    'sort_order' => $definition['sort_order'],
                ],
            );
        }

        $spanishNames = [
            'general' => 'General',
            'politics' => 'Política',
            'economy' => 'Economía',
            'sports' => 'Deportes',
            'culture' => 'Cultura',
            'science' => 'Ciencia',
            'technology' => 'Tecnología',
            'esports' => 'Esports',
            'society' => 'Sociedad',
            'health' => 'Salud',
            'climate' => 'Clima',
            'international' => 'Internacional',
        ];

        foreach ($categories as $slug => $category) {
            NewsCategoryTranslation::query()->updateOrCreate(
                [
                    'news_category_id' => $category->id,
                    'language_code' => 'es',
                ],
                [
                    'name' => $spanishNames[$slug] ?? $category->name,
                    'description' => 'Descripción en español para '.$category->name.'.',
                ],
            );
        }

        return $categories;
    }

    /**
     * @return array<string, NewsSource>
     */
    private function seedSources(array $categories, array $locations): array
    {
        $definitions = [
            [
                'name' => 'Manual Source',
                'type' => 'manual',
                'url' => null,
                'feed_url' => null,
                'language' => 'en',
                'country_code' => null,
                'trust_level' => 5,
            ],
            [
                'name' => 'Manual Source ES',
                'type' => 'manual',
                'url' => null,
                'feed_url' => null,
                'language' => 'es',
                'country_code' => 'ES',
                'trust_level' => 5,
            ],
            [
                'name' => 'Demo RSS Spain',
                'type' => 'rss',
                'url' => 'https://example.com',
                'feed_url' => 'https://example.com/rss/spain.xml',
                'language' => 'es',
                'country_code' => 'ES',
                'trust_level' => 3,
                'default_news_category_id' => $categories['general']->id,
                'default_location_id' => $locations['spain']->id,
                'is_demo' => true,
                'ingestion_notes' => 'Demo RSS source for Spain coverage.',
            ],
            [
                'name' => 'Demo RSS Sports',
                'type' => 'rss',
                'url' => 'https://example.com',
                'feed_url' => 'https://example.com/rss/sports.xml',
                'language' => 'es',
                'country_code' => 'ES',
                'trust_level' => 3,
                'default_news_category_id' => $categories['sports']->id,
                'default_location_id' => $locations['spain']->id,
                'is_demo' => true,
                'ingestion_notes' => 'Demo RSS source for sports coverage.',
            ],
            [
                'name' => 'Demo RSS Technology',
                'type' => 'rss',
                'url' => 'https://example.com/technology',
                'feed_url' => 'https://example.com/rss/technology.xml',
                'language' => 'en',
                'country_code' => null,
                'trust_level' => 3,
                'default_news_category_id' => $categories['technology']->id,
                'default_location_id' => $locations['global']->id,
                'is_demo' => true,
                'ingestion_notes' => 'Demo RSS source for technology coverage.',
            ],
        ];

        $sources = [];

        foreach ($definitions as $definition) {
            $slug = Str::slug($definition['name']);
            $sources[$slug] = NewsSource::query()->updateOrCreate(
                ['slug' => $slug],
                array_merge($definition, ['is_active' => true, 'is_demo' => $definition['is_demo'] ?? false]),
            );
        }

        return $sources;
    }

    /**
     * @param  array<string, Location>  $locations
     * @param  array<string, NewsCategory>  $categories
     * @param  array<string, NewsSource>  $sources
     * @return array<string, NewsItem>
     */
    private function seedNewsItems(array $locations, array $categories, array $sources): array
    {
        $now = now();

        $definitions = [
            ['title' => 'Spain announces a new digital public services plan', 'category' => 'politics', 'source' => 'manual-source', 'location' => 'spain', 'status' => 'selected', 'priority' => 5, 'evergreen' => false, 'offset' => 0],
            ['title' => 'Madrid prepares a weekend cultural agenda', 'category' => 'culture', 'source' => 'manual-source-es', 'location' => 'madrid', 'status' => 'draft', 'priority' => 3, 'evergreen' => false, 'offset' => 0],
            ['title' => 'Local transport update expected in Madrid this week', 'category' => 'society', 'source' => 'demo-rss-spain', 'location' => 'madrid', 'status' => 'collected', 'priority' => 4, 'evergreen' => false, 'offset' => -1],
            ['title' => 'European leaders meet to discuss energy policy', 'category' => 'international', 'source' => 'manual-source', 'location' => 'global', 'status' => 'selected', 'priority' => 4, 'evergreen' => false, 'offset' => -1],
            ['title' => 'New report highlights growth in small business digitization', 'category' => 'economy', 'source' => 'manual-source', 'location' => 'spain', 'status' => 'draft', 'priority' => 4, 'evergreen' => true, 'offset' => -2],
            ['title' => 'Spanish football clubs prepare for a decisive weekend', 'category' => 'sports', 'source' => 'demo-rss-sports', 'location' => 'spain', 'status' => 'selected', 'priority' => 5, 'evergreen' => false, 'offset' => 0],
            ['title' => 'Esports tournament brings international teams to Madrid', 'category' => 'esports', 'source' => 'demo-rss-sports', 'location' => 'madrid', 'status' => 'selected', 'priority' => 4, 'evergreen' => false, 'offset' => 1],
            ['title' => 'Researchers present advances in renewable energy storage', 'category' => 'science', 'source' => 'demo-rss-technology', 'location' => 'global', 'status' => 'draft', 'priority' => 4, 'evergreen' => true, 'offset' => -2],
            ['title' => 'Technology companies expand AI safety teams', 'category' => 'technology', 'source' => 'demo-rss-technology', 'location' => 'global', 'status' => 'selected', 'priority' => 5, 'evergreen' => true, 'offset' => 0],
            ['title' => 'Museums prepare new spring exhibitions', 'category' => 'culture', 'source' => 'manual-source-es', 'location' => 'barcelona', 'status' => 'collected', 'priority' => 2, 'evergreen' => false, 'offset' => -1],
            ['title' => 'Health authorities launch seasonal prevention campaign', 'category' => 'health', 'source' => 'manual-source', 'location' => 'spain', 'status' => 'selected', 'priority' => 4, 'evergreen' => false, 'offset' => 0],
            ['title' => 'Climate experts warn about early heat episodes', 'category' => 'climate', 'source' => 'manual-source-es', 'location' => 'spain', 'status' => 'collected', 'priority' => 5, 'evergreen' => false, 'offset' => -1],
            ['title' => 'Local associations promote neighborhood events', 'category' => 'society', 'source' => 'manual-source-es', 'location' => 'valencia', 'status' => 'archived', 'priority' => 2, 'evergreen' => false, 'offset' => -2],
            ['title' => 'French cities expand sustainable mobility plans', 'category' => 'international', 'source' => 'manual-source', 'location' => 'france', 'status' => 'selected', 'priority' => 3, 'evergreen' => true, 'offset' => -1],
            ['title' => 'Economy analysts review inflation expectations', 'category' => 'economy', 'source' => 'manual-source', 'location' => 'global', 'status' => 'draft', 'priority' => 4, 'evergreen' => false, 'offset' => 0],
            ['title' => 'Science teams test new satellite observation tools', 'category' => 'science', 'source' => 'demo-rss-technology', 'location' => 'global', 'status' => 'draft', 'priority' => 3, 'evergreen' => true, 'offset' => -1],
            ['title' => 'Youth sports programs grow in regional communities', 'category' => 'sports', 'source' => 'manual-source-es', 'location' => 'sevilla', 'status' => 'collected', 'priority' => 3, 'evergreen' => false, 'offset' => -2],
            ['title' => 'Digital creators adapt content formats for short video platforms', 'category' => 'technology', 'source' => 'demo-rss-technology', 'location' => 'global', 'status' => 'selected', 'priority' => 4, 'evergreen' => true, 'offset' => 1],
            ['title' => 'Paris opens a new public innovation lab downtown', 'category' => 'general', 'source' => 'manual-source', 'location' => 'paris', 'status' => 'collected', 'priority' => 3, 'evergreen' => false, 'offset' => 0],
            ['title' => 'Lyon pilots smart district energy dashboards', 'category' => 'technology', 'source' => 'manual-source', 'location' => 'lyon', 'status' => 'draft', 'priority' => 3, 'evergreen' => true, 'offset' => -1],
        ];

        $items = [];

        foreach ($definitions as $index => $definition) {
            $slug = Str::slug($definition['title']);
            $publishedAt = Carbon::instance($now)->addDays($definition['offset'])->setTime(8 + ($index % 8), 0);

            $items[$slug] = NewsItem::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'news_source_id' => $sources[$definition['source']]->id,
                    'news_category_id' => $categories[$definition['category']]->id,
                    'location_id' => $locations[$definition['location']]->id,
                    'title' => $definition['title'],
                    'summary' => 'Demo summary: '.$definition['title'].'.',
                    'body' => 'Demo body for editorial testing about: '.$definition['title'].'. This record exists to exercise collection, selection, and scripting workflows.',
                    'source_url' => 'https://example.com/news/'.$slug,
                    'author' => 'Noticiario Demo Desk',
                    'language' => $locations[$definition['location']]->defaultLanguage?->code ?? 'en',
                    'published_at' => $publishedAt,
                    'collected_at' => (clone $publishedAt)->subHour(),
                    'status' => $definition['status'],
                    'editorial_priority' => $definition['priority'],
                    'is_evergreen' => $definition['evergreen'],
                    'metadata' => [
                        'demo' => true,
                        'source' => 'DemoEditorialSeeder',
                    ],
                ],
            );
        }

        return $items;
    }

    /**
     * @param  array<string, Location>  $locations
     * @return array<string, Edition>
     */
    private function seedEditions(array $locations): array
    {
        $definitions = [
            ['title' => 'Morning Briefing Spain', 'edition_type' => 'morning', 'location' => 'spain', 'language' => 'es', 'status' => 'planning', 'duration' => 90, 'scheduled_offset' => 0],
            ['title' => 'Madrid Local Midday Update', 'edition_type' => 'afternoon', 'location' => 'madrid', 'language' => 'es', 'status' => 'scripting', 'duration' => 75, 'scheduled_offset' => 0],
            ['title' => 'Evening Global Recap', 'edition_type' => 'night', 'location' => 'global', 'language' => 'en', 'status' => 'draft', 'duration' => 120, 'scheduled_offset' => 0],
            ['title' => 'Sports Weekend Preview', 'edition_type' => 'special', 'location' => 'spain', 'language' => 'es', 'status' => 'planning', 'duration' => 60, 'scheduled_offset' => 1],
        ];

        $editions = [];

        foreach ($definitions as $definition) {
            $slug = Str::slug($definition['title']);
            $editions[$slug] = Edition::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'location_id' => $locations[$definition['location']]->id,
                    'title' => $definition['title'],
                    'edition_type' => $definition['edition_type'],
                    'scheduled_for' => now()->addDays($definition['scheduled_offset'])->setTime(7, 30),
                    'language' => $definition['language'],
                    'status' => $definition['status'],
                    'target_duration_seconds' => $definition['duration'],
                    'description' => 'Demo edition for workflow validation.',
                    'metadata' => ['demo' => true, 'source' => 'DemoEditorialSeeder'],
                ],
            );
        }

        return $editions;
    }

    /**
     * @param  array<string, Edition>  $editions
     * @param  array<string, NewsItem>  $newsItems
     */
    private function attachNewsItemsToEditions(array $editions, array $newsItems): void
    {
        $map = [
            'morning-briefing-spain' => [
                ['slug' => 'spain-announces-a-new-digital-public-services-plan', 'sort_order' => 1, 'editorial_angle' => 'National implementation milestones', 'included' => true],
                ['slug' => 'health-authorities-launch-seasonal-prevention-campaign', 'sort_order' => 2, 'editorial_angle' => 'Public service guidance', 'included' => true],
                ['slug' => 'climate-experts-warn-about-early-heat-episodes', 'sort_order' => 3, 'editorial_angle' => 'Community readiness', 'included' => false],
            ],
            'madrid-local-midday-update' => [
                ['slug' => 'madrid-prepares-a-weekend-cultural-agenda', 'sort_order' => 1, 'editorial_angle' => 'City cultural highlights', 'included' => true],
                ['slug' => 'local-transport-update-expected-in-madrid-this-week', 'sort_order' => 2, 'editorial_angle' => 'Commuter impacts', 'included' => true],
                ['slug' => 'esports-tournament-brings-international-teams-to-madrid', 'sort_order' => 3, 'editorial_angle' => 'Economic and tourism boost', 'included' => true],
            ],
            'evening-global-recap' => [
                ['slug' => 'european-leaders-meet-to-discuss-energy-policy', 'sort_order' => 1, 'editorial_angle' => 'Regional policy consequences', 'included' => true],
                ['slug' => 'technology-companies-expand-ai-safety-teams', 'sort_order' => 2, 'editorial_angle' => 'Industry governance trends', 'included' => true],
                ['slug' => 'economy-analysts-review-inflation-expectations', 'sort_order' => 3, 'editorial_angle' => 'Consumer outlook', 'included' => false],
            ],
            'sports-weekend-preview' => [
                ['slug' => 'spanish-football-clubs-prepare-for-a-decisive-weekend', 'sort_order' => 1, 'editorial_angle' => 'Matchday narratives', 'included' => true],
                ['slug' => 'youth-sports-programs-grow-in-regional-communities', 'sort_order' => 2, 'editorial_angle' => 'Grassroots momentum', 'included' => true],
                ['slug' => 'esports-tournament-brings-international-teams-to-madrid', 'sort_order' => 3, 'editorial_angle' => 'Cross-audience sports coverage', 'included' => false],
            ],
        ];

        foreach ($map as $editionSlug => $items) {
            $edition = $editions[$editionSlug];

            $syncData = [];
            foreach ($items as $item) {
                $syncData[$newsItems[$item['slug']]->id] = [
                    'sort_order' => $item['sort_order'],
                    'editorial_angle' => $item['editorial_angle'],
                    'included_in_script' => $item['included'],
                ];
            }

            $edition->newsItems()->syncWithoutDetaching($syncData);
        }
    }

    /**
     * @param  array<string, Edition>  $editions
     */
    private function seedScripts(array $editions): void
    {
        $definitions = [
            ['edition' => 'morning-briefing-spain', 'title' => 'Draft script for Morning Briefing Spain', 'status' => 'draft', 'duration' => 90],
            ['edition' => 'madrid-local-midday-update', 'title' => 'Review script for Madrid Local Midday Update', 'status' => 'review', 'duration' => 75],
            ['edition' => 'evening-global-recap', 'title' => 'Draft script for Evening Global Recap', 'status' => 'draft', 'duration' => 120],
            ['edition' => 'sports-weekend-preview', 'title' => 'Draft script for Sports Weekend Preview', 'status' => 'draft', 'duration' => 60],
        ];

        foreach ($definitions as $definition) {
            $edition = $editions[$definition['edition']];

            Script::query()->updateOrCreate(
                [
                    'edition_id' => $edition->id,
                    'title' => $definition['title'],
                ],
                [
                    'status' => $definition['status'],
                    'language' => $edition->language ?? 'en',
                    'intro' => 'Welcome to this demo editorial script.',
                    'body' => 'We open with the key update, then move to context, impact, and what to watch next in the next 24 hours.',
                    'outro' => 'That concludes this Noticiario briefing.',
                    'estimated_duration_seconds' => $definition['duration'],
                    'metadata' => [
                        'demo' => true,
                        'source' => 'DemoEditorialSeeder',
                    ],
                ],
            );
        }
    }

    /**
     * @param  array<string, Location>  $locations
     */
    private function seedEditorialTemplates(array $locations): void
    {
        $languages = Language::query()->whereIn('code', ['en', 'es', 'fr'])->get()->keyBy('code');

        $definitions = [
            ['name' => 'Morning Briefing', 'slug' => 'morning-briefing', 'language_id' => $languages->get('en')?->id, 'location_id' => $locations['global']->id, 'edition_type' => 'morning', 'target_duration_seconds' => 90],
            ['name' => 'Spanish Morning Briefing', 'slug' => 'spanish-morning-briefing', 'language_id' => $languages->get('es')?->id, 'location_id' => $locations['spain']->id, 'edition_type' => 'morning', 'target_duration_seconds' => 90],
            ['name' => 'Madrid Local Update', 'slug' => 'madrid-local-update', 'language_id' => $languages->get('es')?->id, 'location_id' => $locations['madrid']->id, 'edition_type' => 'afternoon', 'target_duration_seconds' => 75],
            ['name' => 'Evening Global Recap', 'slug' => 'evening-global-recap-template', 'language_id' => $languages->get('en')?->id, 'location_id' => $locations['global']->id, 'edition_type' => 'night', 'target_duration_seconds' => 120],
            ['name' => 'Sports Preview', 'slug' => 'sports-preview-template', 'language_id' => $languages->get('es')?->id ?? $languages->get('en')?->id, 'location_id' => $locations['spain']->id, 'edition_type' => 'special', 'target_duration_seconds' => 60],
        ];

        foreach ($definitions as $definition) {
            EditorialTemplate::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                array_merge($definition, [
                    'description' => 'Demo editorial template for '.$definition['name'],
                    'intro_template' => 'Intro: {{edition_title}} ({{location_name}})',
                    'body_template' => "{{news_items}}",
                    'outro_template' => 'Outro for {{edition_title}} on {{date}}',
                    'is_active' => true,
                    'sort_order' => 0,
                ]),
            );
        }
    }

    /**
     * @return array<string, AiProvider>
     */
    private function seedAiProviders(): array
    {
        $definitions = [
            [
                'name' => 'Mock AI Provider',
                'slug' => 'mock-ai-provider',
                'provider_type' => 'mock',
                'default_model' => 'mock-editorial-v1',
                'supports_web_search' => true,
                'supports_json_mode' => true,
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'name' => 'OpenRouter',
                'slug' => 'openrouter',
                'provider_type' => 'openrouter',
                'api_key_env' => 'OPENROUTER_API_KEY',
                'default_model' => 'openrouter/auto',
                'supports_web_search' => true,
                'supports_json_mode' => true,
                'is_active' => false,
                'is_default' => false,
            ],
            [
                'name' => 'OpenAI',
                'slug' => 'openai',
                'provider_type' => 'openai',
                'api_key_env' => 'OPENAI_API_KEY',
                'default_model' => 'gpt-4.1-mini',
                'supports_web_search' => false,
                'supports_json_mode' => true,
                'is_active' => false,
                'is_default' => false,
            ],
        ];

        $providers = [];

        foreach ($definitions as $definition) {
            $providers[$definition['slug']] = AiProvider::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                array_merge($definition, ['base_url' => null]),
            );
        }

        return $providers;
    }

    /**
     * @param  array<string, Location>  $locations
     * @param  array<string, NewsCategory>  $categories
     * @return array<string, AiPromptTemplate>
     */
    private function seedAiPromptTemplates(array $locations, array $categories): array
    {
        $languages = Language::query()->whereIn('code', ['en', 'es'])->get()->keyBy('code');

        $definitions = [
            [
                'name' => 'General Editorial Research',
                'slug' => 'general-editorial-research',
                'type' => 'editorial_research',
                'expected_output_format' => 'json',
                'system_prompt' => 'You are an editorial research assistant for a short-form digital news bulletin platform. You must find relevant, recent and verifiable news topics for the requested location and category. Return concise structured output.',
                'user_prompt' => "Research concise candidate stories for {{location_name}} and {{category_name}}.\nEdition type: {{edition_type}}\nLanguage: {{language_name}}\nDuration: {{target_duration_seconds}}\nDate: {{date}}\nInstructions: {{editorial_instructions}}\nReturn JSON with candidate news items including title, summary, source_hint, suggested_category, suggested_location, relevance_score, editorial_angle, why_it_matters.",
                'language_id' => null,
                'location_id' => null,
                'news_category_id' => null,
            ],
            [
                'name' => 'Short Script Generation',
                'slug' => 'short-script-generation',
                'type' => 'script_generation',
                'expected_output_format' => 'text',
                'system_prompt' => 'You are an experienced news script editor. Write clear, concise scripts for short digital news bulletins.',
                'user_prompt' => "Create a short script for {{edition_title}} in {{language_name}} for {{location_name}}.\nDuration: {{target_duration_seconds}}\nNews items:\n{{selected_news_items}}\nTemplate: {{editorial_template}}\nTone: {{tone}}",
                'language_id' => null,
                'location_id' => null,
                'news_category_id' => null,
            ],
            [
                'name' => 'Spanish Local News Brief',
                'slug' => 'spanish-local-news-brief',
                'type' => 'script_generation',
                'expected_output_format' => 'text',
                'system_prompt' => 'You are an experienced news script editor. Write clear, concise scripts for short digital news bulletins.',
                'user_prompt' => "Escribe un guion breve para {{edition_title}} en {{language_name}} para {{location_name}}.\nDuración: {{target_duration_seconds}}\nNoticias:\n{{selected_news_items}}",
                'language_id' => $languages->get('es')?->id,
                'location_id' => null,
                'news_category_id' => null,
            ],
            [
                'name' => 'Sports Preview Script',
                'slug' => 'sports-preview-script',
                'type' => 'script_generation',
                'expected_output_format' => 'text',
                'system_prompt' => 'You are an experienced news script editor. Write clear, concise scripts for short digital news bulletins.',
                'user_prompt' => "Draft a sports preview script for {{edition_title}} in {{language_name}}.\nDuration: {{target_duration_seconds}}\nItems:\n{{selected_news_items}}",
                'language_id' => null,
                'location_id' => null,
                'news_category_id' => $categories['sports']->id,
            ],
        ];

        $templates = [];

        foreach ($definitions as $definition) {
            $templates[$definition['slug']] = AiPromptTemplate::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                array_merge($definition, [
                    'description' => 'Demo AI prompt template for '.$definition['name'],
                    'edition_type' => null,
                    'is_active' => true,
                    'sort_order' => 0,
                ]),
            );
        }

        return $templates;
    }

    /**
     * @param  array<string, Location>  $locations
     * @param  array<string, NewsCategory>  $categories
     * @param  array<string, AiProvider>  $providers
     * @param  array<string, AiPromptTemplate>  $promptTemplates
     */
    private function seedEditorialRequests(array $locations, array $categories, array $providers, array $promptTemplates): void
    {
        $spanish = Language::query()->where('code', 'es')->first();

        EditorialRequest::query()->updateOrCreate(
            ['title' => 'Demo Madrid Afternoon Briefing'],
            [
                'requested_by' => null,
                'ai_provider_id' => $providers['mock-ai-provider']->id,
                'ai_prompt_template_id' => $promptTemplates['general-editorial-research']->id,
                'location_id' => $locations['madrid']->id,
                'news_category_id' => $categories['general']->id,
                'language_id' => $spanish?->id,
                'edition_type' => 'afternoon',
                'target_duration_seconds' => 90,
                'status' => 'draft',
                'editorial_instructions' => 'Focus on practical city impact and keep the pace concise.',
            ],
        );
    }

}
