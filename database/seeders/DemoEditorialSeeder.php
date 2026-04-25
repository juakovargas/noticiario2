<?php

namespace Database\Seeders;

use App\Models\Edition;
use App\Models\Location;
use App\Models\NewsCategory;
use App\Models\NewsItem;
use App\Models\NewsSource;
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
        $sources = $this->seedSources();
        $newsItems = $this->seedNewsItems($locations, $categories, $sources);
        $editions = $this->seedEditions($locations);

        $this->attachNewsItemsToEditions($editions, $newsItems);
        $this->seedScripts($editions);
    }

    /**
     * @return array<string, Location>
     */
    private function seedLocations(): array
    {
        $global = $this->upsertLocation('Global', [
            'type' => 'global',
            'country_code' => null,
            'timezone' => 'UTC',
            'sort_order' => 0,
        ]);

        $spain = $this->upsertLocation('Spain', [
            'parent_id' => $global->id,
            'type' => 'country',
            'country_code' => 'ES',
            'timezone' => 'Europe/Madrid',
            'sort_order' => 10,
        ]);

        $france = $this->upsertLocation('France', [
            'parent_id' => $global->id,
            'type' => 'country',
            'country_code' => 'FR',
            'timezone' => 'Europe/Paris',
            'sort_order' => 20,
        ]);

        $us = $this->upsertLocation('United States', [
            'parent_id' => $global->id,
            'type' => 'country',
            'country_code' => 'US',
            'timezone' => 'America/New_York',
            'sort_order' => 30,
        ]);

        $madrid = $this->upsertLocation('Madrid', [
            'parent_id' => $spain->id,
            'type' => 'region',
            'country_code' => 'ES',
            'timezone' => 'Europe/Madrid',
            'sort_order' => 11,
        ]);

        $barcelona = $this->upsertLocation('Barcelona', [
            'parent_id' => $spain->id,
            'type' => 'city',
            'country_code' => 'ES',
            'timezone' => 'Europe/Madrid',
            'sort_order' => 12,
        ]);

        $valencia = $this->upsertLocation('Valencia', [
            'parent_id' => $spain->id,
            'type' => 'city',
            'country_code' => 'ES',
            'timezone' => 'Europe/Madrid',
            'sort_order' => 13,
        ]);

        $sevilla = $this->upsertLocation('Sevilla', [
            'parent_id' => $spain->id,
            'type' => 'city',
            'country_code' => 'ES',
            'timezone' => 'Europe/Madrid',
            'sort_order' => 14,
        ]);

        $paris = $this->upsertLocation('Paris', [
            'parent_id' => $france->id,
            'type' => 'city',
            'country_code' => 'FR',
            'timezone' => 'Europe/Paris',
            'sort_order' => 21,
        ]);

        $lyon = $this->upsertLocation('Lyon', [
            'parent_id' => $france->id,
            'type' => 'city',
            'country_code' => 'FR',
            'timezone' => 'Europe/Paris',
            'sort_order' => 22,
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
            $categories[Str::slug($definition['name'])] = NewsCategory::query()->updateOrCreate(
                ['slug' => Str::slug($definition['name'])],
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

        return $categories;
    }

    /**
     * @return array<string, NewsSource>
     */
    private function seedSources(): array
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
            ],
            [
                'name' => 'Demo RSS Sports',
                'type' => 'rss',
                'url' => 'https://example.com',
                'feed_url' => 'https://example.com/rss/sports.xml',
                'language' => 'es',
                'country_code' => 'ES',
                'trust_level' => 3,
            ],
            [
                'name' => 'Demo Technology Desk',
                'type' => 'website',
                'url' => 'https://example.com/technology',
                'feed_url' => null,
                'language' => 'en',
                'country_code' => null,
                'trust_level' => 3,
            ],
        ];

        $sources = [];

        foreach ($definitions as $definition) {
            $slug = Str::slug($definition['name']);
            $sources[$slug] = NewsSource::query()->updateOrCreate(
                ['slug' => $slug],
                array_merge($definition, ['is_active' => true]),
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
            ['title' => 'Researchers present advances in renewable energy storage', 'category' => 'science', 'source' => 'demo-technology-desk', 'location' => 'global', 'status' => 'draft', 'priority' => 4, 'evergreen' => true, 'offset' => -2],
            ['title' => 'Technology companies expand AI safety teams', 'category' => 'technology', 'source' => 'demo-technology-desk', 'location' => 'global', 'status' => 'selected', 'priority' => 5, 'evergreen' => true, 'offset' => 0],
            ['title' => 'Museums prepare new spring exhibitions', 'category' => 'culture', 'source' => 'manual-source-es', 'location' => 'barcelona', 'status' => 'collected', 'priority' => 2, 'evergreen' => false, 'offset' => -1],
            ['title' => 'Health authorities launch seasonal prevention campaign', 'category' => 'health', 'source' => 'manual-source', 'location' => 'spain', 'status' => 'selected', 'priority' => 4, 'evergreen' => false, 'offset' => 0],
            ['title' => 'Climate experts warn about early heat episodes', 'category' => 'climate', 'source' => 'manual-source-es', 'location' => 'spain', 'status' => 'collected', 'priority' => 5, 'evergreen' => false, 'offset' => -1],
            ['title' => 'Local associations promote neighborhood events', 'category' => 'society', 'source' => 'manual-source-es', 'location' => 'valencia', 'status' => 'archived', 'priority' => 2, 'evergreen' => false, 'offset' => -2],
            ['title' => 'French cities expand sustainable mobility plans', 'category' => 'international', 'source' => 'manual-source', 'location' => 'france', 'status' => 'selected', 'priority' => 3, 'evergreen' => true, 'offset' => -1],
            ['title' => 'Economy analysts review inflation expectations', 'category' => 'economy', 'source' => 'manual-source', 'location' => 'global', 'status' => 'draft', 'priority' => 4, 'evergreen' => false, 'offset' => 0],
            ['title' => 'Science teams test new satellite observation tools', 'category' => 'science', 'source' => 'demo-technology-desk', 'location' => 'global', 'status' => 'draft', 'priority' => 3, 'evergreen' => true, 'offset' => -1],
            ['title' => 'Youth sports programs grow in regional communities', 'category' => 'sports', 'source' => 'manual-source-es', 'location' => 'sevilla', 'status' => 'collected', 'priority' => 3, 'evergreen' => false, 'offset' => -2],
            ['title' => 'Digital creators adapt content formats for short video platforms', 'category' => 'technology', 'source' => 'demo-technology-desk', 'location' => 'global', 'status' => 'selected', 'priority' => 4, 'evergreen' => true, 'offset' => 1],
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
                    'language' => 'en',
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
            ['title' => 'Morning Briefing Spain', 'edition_type' => 'morning', 'location' => 'spain', 'language' => 'en', 'status' => 'planning', 'duration' => 90, 'scheduled_offset' => 0],
            ['title' => 'Madrid Local Midday Update', 'edition_type' => 'afternoon', 'location' => 'madrid', 'language' => 'en', 'status' => 'scripting', 'duration' => 75, 'scheduled_offset' => 0],
            ['title' => 'Evening Global Recap', 'edition_type' => 'night', 'location' => 'global', 'language' => 'en', 'status' => 'draft', 'duration' => 120, 'scheduled_offset' => 0],
            ['title' => 'Sports Weekend Preview', 'edition_type' => 'special', 'location' => 'spain', 'language' => 'en', 'status' => 'planning', 'duration' => 60, 'scheduled_offset' => 1],
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
                    'language' => 'en',
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
}
