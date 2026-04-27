<?php

namespace Tests\Feature;

use App\Models\Edition;
use App\Models\AiPromptTemplate;
use App\Models\AiProvider;
use App\Models\EditorialTemplate;
use App\Models\EditorialRequest;
use App\Models\EditorialSchedule;
use App\Models\EditorialScheduleRun;
use App\Models\Language;
use App\Models\Location;
use App\Models\NewsCategory;
use App\Models\Script;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoEditorialSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DemoSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_and_permissions_seeder_can_run(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertDatabaseHas('permissions', ['name' => 'admin.access']);
        $this->assertDatabaseHas('permissions', ['name' => 'editor.access']);
        $this->assertDatabaseHas('permissions', ['name' => 'viewer.access']);
        $this->assertNotNull(Role::findByName('admin'));
        $this->assertNotNull(Role::findByName('editor'));
        $this->assertNotNull(Role::findByName('viewer'));
    }


    public function test_basic_roles_exist_with_expected_access_boundaries(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $superadmin = Role::findByName('superadmin');
        $admin = Role::findByName('admin');
        $editor = Role::findByName('editor');
        $viewer = Role::findByName('viewer');

        $this->assertNotNull($superadmin);
        $this->assertNotNull($admin);
        $this->assertNotNull($editor);
        $this->assertNotNull($viewer);

        $this->assertTrue($admin->hasPermissionTo('admin.access'));
        $this->assertFalse($editor->hasPermissionTo('admin.access'));
        $this->assertFalse($viewer->hasPermissionTo('admin.access'));
        $this->assertFalse($viewer->hasPermissionTo('editor.access'));
    }

    public function test_roles_and_permissions_seeder_is_idempotent_for_basic_roles(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertSame(1, Role::query()->where('name', 'superadmin')->count());
        $this->assertSame(1, Role::query()->where('name', 'admin')->count());
        $this->assertSame(1, Role::query()->where('name', 'editor')->count());
        $this->assertSame(1, Role::query()->where('name', 'viewer')->count());
    }

    public function test_demo_users_exist_after_testing_seeding_with_expected_roles(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $editor = User::query()->where('email', 'editor@example.com')->firstOrFail();
        $viewer = User::query()->where('email', 'viewer@example.com')->firstOrFail();

        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($editor->hasRole('editor'));
        $this->assertTrue($viewer->hasRole('viewer'));
        $this->assertSame('en', $admin->preferred_locale);
        $this->assertSame('es', $editor->preferred_locale);
        $this->assertSame('es', $viewer->preferred_locale);
        $this->assertSame('Europe/Madrid', $admin->timezone);
    }

    public function test_demo_editorial_seeder_runs_safely_and_does_not_duplicate_core_records(): void
    {
        $this->seed([RolesAndPermissionsSeeder::class, DemoEditorialSeeder::class]);

        $locationsCount = Location::query()->count();
        $categoriesCount = NewsCategory::query()->count();
        $editionsCount = Edition::query()->count();
        $scriptsCount = Script::query()->count();
        $templatesCount = EditorialTemplate::query()->count();
        $providersCount = AiProvider::query()->count();
        $promptTemplatesCount = AiPromptTemplate::query()->count();
        $schedulesCount = EditorialSchedule::query()->count();
        $scheduleRunsCount = EditorialScheduleRun::query()->count();

        $this->seed(DemoEditorialSeeder::class);

        $this->assertSame($locationsCount, Location::query()->count());
        $this->assertSame($categoriesCount, NewsCategory::query()->count());
        $this->assertSame($editionsCount, Edition::query()->count());
        $this->assertSame($scriptsCount, Script::query()->count());
        $this->assertSame($templatesCount, EditorialTemplate::query()->count());
        $this->assertSame($providersCount, AiProvider::query()->count());
        $this->assertSame($promptTemplatesCount, AiPromptTemplate::query()->count());
        $this->assertSame($schedulesCount, EditorialSchedule::query()->count());
        $this->assertSame($scheduleRunsCount, EditorialScheduleRun::query()->count());
    }

    public function test_expected_demo_editorial_records_exist(): void
    {
        $this->seed([RolesAndPermissionsSeeder::class, DemoEditorialSeeder::class]);

        foreach (['global', 'spain', 'france', 'united-states', 'madrid', 'barcelona', 'valencia', 'sevilla', 'paris', 'lyon'] as $slug) {
            $this->assertDatabaseHas('locations', ['slug' => $slug]);
        }

        foreach (['general', 'politics', 'economy', 'sports', 'culture', 'science', 'technology', 'esports', 'society', 'health', 'climate', 'international'] as $slug) {
            $this->assertDatabaseHas('news_categories', ['slug' => $slug]);
        }

        $this->assertDatabaseHas('news_category_translations', ['language_code' => 'es', 'name' => 'Política']);

        $this->assertDatabaseHas('languages', ['code' => 'en']);
        $this->assertDatabaseHas('languages', ['code' => 'es']);
        $this->assertDatabaseHas('languages', ['code' => 'fr']);
        $this->assertDatabaseHas('locations', ['slug' => 'spain', 'default_language_id' => Language::query()->where('code', 'es')->value('id')]);

        foreach (['morning-briefing-spain', 'madrid-local-midday-update', 'evening-global-recap', 'sports-weekend-preview'] as $slug) {
            $this->assertDatabaseHas('editions', ['slug' => $slug]);
        }

        $this->assertDatabaseHas('scripts', ['title' => 'Draft script for Morning Briefing Spain']);
        $this->assertDatabaseHas('editorial_templates', ['slug' => 'morning-briefing']);
        $this->assertDatabaseHas('editorial_templates', ['slug' => 'madrid-local-update']);
        $this->assertDatabaseHas('ai_providers', ['slug' => 'mock-ai-provider', 'provider_type' => 'mock', 'is_default' => true]);
        $this->assertDatabaseHas('ai_prompt_templates', ['slug' => 'general-editorial-research', 'type' => 'editorial_research']);
        $this->assertDatabaseHas('ai_prompt_templates', ['slug' => 'short-script-generation', 'type' => 'script_generation']);
        $this->assertDatabaseHas('editorial_requests', ['title' => 'Demo Madrid Afternoon Briefing', 'status' => 'draft']);
        $this->assertDatabaseHas('scripts', ['title' => 'Review script for Madrid Local Midday Update']);
        $this->assertDatabaseHas('editorial_schedules', ['slug' => 'spain-morning-briefing', 'frequency_type' => 'daily']);
        $this->assertDatabaseHas('editorial_schedules', ['slug' => 'spain-afternoon-briefing', 'scheduled_time' => '15:00:00']);
        $this->assertDatabaseHas('editorial_schedules', ['slug' => 'spain-night-recap', 'scheduled_time' => '21:00:00']);
        $this->assertDatabaseHas('editorial_schedules', ['slug' => 'madrid-local-morning', 'frequency_type' => 'weekdays']);
        $this->assertDatabaseHas('editorial_schedules', ['slug' => 'spain-sports-evening']);
        $this->assertDatabaseHas('editorial_schedules', ['slug' => 'super-bowl-special-demo', 'frequency_type' => 'once']);

        $this->assertTrue(
            Edition::query()->whereHas('newsItems')->exists(),
            'At least one edition should have attached news items.',
        );
    }
    public function test_demo_rss_sources_have_defaults_and_do_not_duplicate(): void
    {
        $this->seed([RolesAndPermissionsSeeder::class, DemoEditorialSeeder::class]);

        $this->assertDatabaseHas('news_sources', [
            'slug' => 'demo-rss-spain',
            'type' => 'rss',
            'feed_url' => 'https://example.com/rss/spain.xml',
            'language' => 'es',
            'is_demo' => true,
        ]);

        $this->assertDatabaseHas('news_sources', [
            'slug' => 'demo-rss-sports',
            'type' => 'rss',
            'feed_url' => 'https://example.com/rss/sports.xml',
            'language' => 'es',
            'is_demo' => true,
        ]);

        $this->assertDatabaseHas('news_sources', [
            'slug' => 'demo-rss-technology',
            'type' => 'rss',
            'feed_url' => 'https://example.com/rss/technology.xml',
            'language' => 'en',
            'is_demo' => true,
        ]);

        $this->assertDatabaseMissing('news_sources', [
            'slug' => 'demo-rss-spain',
            'default_news_category_id' => null,
        ]);

        $this->assertDatabaseMissing('news_sources', [
            'slug' => 'demo-rss-spain',
            'default_location_id' => null,
        ]);

        $count = \App\Models\NewsSource::query()->count();

        $this->seed(DemoEditorialSeeder::class);

        $this->assertSame($count, \App\Models\NewsSource::query()->count());
    }

}
