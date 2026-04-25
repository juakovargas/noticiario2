<?php

namespace Tests\Feature;

use App\Models\Edition;
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

    public function test_demo_users_exist_after_testing_seeding_with_expected_roles(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $editor = User::query()->where('email', 'editor@example.com')->firstOrFail();
        $viewer = User::query()->where('email', 'viewer@example.com')->firstOrFail();

        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($editor->hasRole('editor'));
        $this->assertTrue($viewer->hasRole('viewer'));
    }

    public function test_demo_editorial_seeder_runs_safely_and_does_not_duplicate_core_records(): void
    {
        $this->seed([RolesAndPermissionsSeeder::class, DemoEditorialSeeder::class]);

        $locationsCount = Location::query()->count();
        $categoriesCount = NewsCategory::query()->count();
        $editionsCount = Edition::query()->count();
        $scriptsCount = Script::query()->count();

        $this->seed(DemoEditorialSeeder::class);

        $this->assertSame($locationsCount, Location::query()->count());
        $this->assertSame($categoriesCount, NewsCategory::query()->count());
        $this->assertSame($editionsCount, Edition::query()->count());
        $this->assertSame($scriptsCount, Script::query()->count());
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

        foreach (['morning-briefing-spain', 'madrid-local-midday-update', 'evening-global-recap', 'sports-weekend-preview'] as $slug) {
            $this->assertDatabaseHas('editions', ['slug' => $slug]);
        }

        $this->assertDatabaseHas('scripts', ['title' => 'Draft script for Morning Briefing Spain']);
        $this->assertDatabaseHas('scripts', ['title' => 'Review script for Madrid Local Midday Update']);

        $this->assertTrue(
            Edition::query()->whereHas('newsItems')->exists(),
            'At least one edition should have attached news items.',
        );
    }
}
