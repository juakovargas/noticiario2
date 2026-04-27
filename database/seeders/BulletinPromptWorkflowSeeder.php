<?php

namespace Database\Seeders;

use App\Models\BulletinPromptRun;
use App\Models\BulletinType;
use App\Models\Language;
use App\Models\Location;
use App\Models\NewsCategory;
use App\Models\PromptProfile;
use App\Services\PromptGeneration\BulletinPromptGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BulletinPromptWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $profiles = $this->seedPromptProfiles();
        $types = $this->seedBulletinTypes($profiles);

        $run = BulletinPromptRun::query()->updateOrCreate(
            ['title' => $types['spain-morning-general-news']->name.' - Demo Run'],
            [
                'bulletin_type_id' => $types['spain-morning-general-news']->id,
                'prompt_profile_id' => $profiles['balanced-news']->id,
                'status' => 'prompt_ready',
                'scheduled_for' => now()->addHour(),
            ],
        );

        if (! $run->generated_prompt) {
            $prompt = app(BulletinPromptGenerator::class)->generate($run->fresh(['bulletinType.location', 'bulletinType.newsCategory', 'bulletinType.language', 'promptProfile']));
            $run->update(['generated_prompt' => $prompt, 'prompt_generated_at' => now()]);
        }
    }

    private function seedPromptProfiles(): array
    {
        $definitions = [
            'Balanced News' => ['happiness_level' => 5, 'optimism_level' => 5, 'seriousness_level' => 7, 'humor_level' => 0, 'irony_level' => 0, 'formality_level' => 7, 'source_strictness_level' => 8, 'is_default' => true],
            'Positive News' => ['happiness_level' => 9, 'optimism_level' => 8, 'negativity_tolerance' => 2, 'seriousness_level' => 6, 'humor_level' => 1, 'source_strictness_level' => 8],
            'Light Humour Briefing' => ['happiness_level' => 7, 'optimism_level' => 7, 'humor_level' => 4, 'irony_level' => 2, 'seriousness_level' => 5, 'formality_level' => 4, 'source_strictness_level' => 7],
            'Serious Institutional' => ['happiness_level' => 4, 'optimism_level' => 5, 'humor_level' => 0, 'irony_level' => 0, 'seriousness_level' => 10, 'formality_level' => 10, 'source_strictness_level' => 10],
        ];

        $profiles = [];

        foreach ($definitions as $name => $attrs) {
            $profiles[Str::slug($name)] = PromptProfile::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                array_merge([
                    'name' => $name,
                    'description' => $name.' demo profile',
                    'happiness_level' => 5,
                    'optimism_level' => 5,
                    'seriousness_level' => 5,
                    'humor_level' => 0,
                    'irony_level' => 0,
                    'formality_level' => 5,
                    'negativity_tolerance' => 5,
                    'controversy_tolerance' => 5,
                    'source_strictness_level' => 7,
                    'is_active' => true,
                    'is_default' => false,
                    'sort_order' => 0,
                ], $attrs),
            );
        }

        if (isset($profiles['balanced-news'])) {
            PromptProfile::query()->whereKeyNot($profiles['balanced-news']->id)->update(['is_default' => false]);
        }

        return $profiles;
    }

    private function seedBulletinTypes(array $profiles): array
    {
        $spain = Location::query()->where('slug', 'spain')->first();
        $madrid = Location::query()->where('slug', 'madrid')->first();
        $global = Location::query()->where('slug', 'global')->first();
        $us = Location::query()->where('slug', 'united-states')->first();

        $general = NewsCategory::query()->where('slug', 'general')->first();
        $sports = NewsCategory::query()->where('slug', 'sports')->first();
        $technology = NewsCategory::query()->where('slug', 'technology')->first();

        $es = Language::query()->where('code', 'es')->first();
        $en = Language::query()->where('code', 'en')->first();

        $definitions = [
            ['name' => 'Spain Morning General News', 'location_id' => $spain?->id, 'news_category_id' => $general?->id, 'language_id' => $es?->id, 'edition_type' => 'morning', 'target_duration_seconds' => 90, 'default_prompt_profile_id' => $profiles['balanced-news']->id ?? null],
            ['name' => 'Spain Positive News Briefing', 'location_id' => $spain?->id, 'news_category_id' => $general?->id, 'language_id' => $es?->id, 'edition_type' => 'morning', 'target_duration_seconds' => 90, 'default_prompt_profile_id' => $profiles['positive-news']->id ?? null],
            ['name' => 'Madrid Local News', 'location_id' => $madrid?->id, 'news_category_id' => $general?->id, 'language_id' => $es?->id, 'edition_type' => 'afternoon', 'target_duration_seconds' => 75, 'default_prompt_profile_id' => $profiles['balanced-news']->id ?? null],
            ['name' => 'Spain Sports Daily', 'location_id' => $spain?->id, 'news_category_id' => $sports?->id, 'language_id' => $es?->id, 'edition_type' => 'special', 'target_duration_seconds' => 75, 'default_prompt_profile_id' => $profiles['light-humour-briefing']->id ?? ($profiles['balanced-news']->id ?? null)],
            ['name' => 'Global Technology Brief', 'location_id' => $global?->id, 'news_category_id' => $technology?->id, 'language_id' => $en?->id, 'edition_type' => 'special', 'target_duration_seconds' => 90, 'default_prompt_profile_id' => $profiles['balanced-news']->id ?? null],
            ['name' => 'Super Bowl Special', 'location_id' => $us?->id ?? $global?->id, 'news_category_id' => $sports?->id, 'language_id' => $en?->id, 'edition_type' => 'special', 'target_duration_seconds' => 120, 'default_prompt_profile_id' => $profiles['light-humour-briefing']->id ?? null],
        ];

        $types = [];

        foreach ($definitions as $index => $data) {
            $slug = Str::slug($data['name']);
            $types[$slug] = BulletinType::query()->updateOrCreate(
                ['slug' => $slug],
                array_merge($data, [
                    'description' => $data['name'].' demo bulletin type',
                    'default_timezone' => 'Europe/Madrid',
                    'is_active' => true,
                    'sort_order' => $index,
                ]),
            );
        }

        return $types;
    }
}
