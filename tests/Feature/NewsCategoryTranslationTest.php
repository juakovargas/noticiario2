<?php

namespace Tests\Feature;

use App\Models\NewsCategory;
use Database\Seeders\DemoEditorialSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsCategoryTranslationTest extends TestCase
{
    use RefreshDatabase;

    public function test_spanish_category_translations_are_seeded(): void
    {
        $this->seed([RolesAndPermissionsSeeder::class, DemoEditorialSeeder::class]);

        $this->assertDatabaseHas('news_category_translations', [
            'language_code' => 'es',
            'name' => 'Política',
        ]);
    }

    public function test_category_display_name_returns_spanish_when_locale_is_es(): void
    {
        $this->seed([RolesAndPermissionsSeeder::class, DemoEditorialSeeder::class]);
        app()->setLocale('es');

        $category = NewsCategory::query()->with('translations')->where('slug', 'politics')->firstOrFail();

        $this->assertSame('Política', $category->displayName('es'));
    }

    public function test_category_display_name_falls_back_when_translation_missing(): void
    {
        $category = NewsCategory::factory()->create(['name' => 'Unique Name']);

        $this->assertSame('Unique Name', $category->displayName('es'));
    }

    public function test_demo_editorial_seeder_can_run_twice_without_duplicate_category_translations(): void
    {
        $this->seed([RolesAndPermissionsSeeder::class, DemoEditorialSeeder::class]);
        $count = \DB::table('news_category_translations')->count();

        $this->seed(DemoEditorialSeeder::class);

        $this->assertSame($count, \DB::table('news_category_translations')->count());
    }
}
