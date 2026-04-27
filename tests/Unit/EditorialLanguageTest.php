<?php

namespace Tests\Unit;

use App\Models\Language;
use App\Models\Location;
use App\Support\EditorialLanguage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditorialLanguageTest extends TestCase
{
    use RefreshDatabase;

    public function test_explicit_language_wins(): void
    {
        $service = app(EditorialLanguage::class);
        $location = Location::factory()->create();

        $this->assertSame('fr', $service->resolveCode($location, 'fr'));
    }

    public function test_location_default_language_is_used_when_explicit_is_empty(): void
    {
        $spanish = Language::factory()->create(['code' => 'es']);
        $location = Location::factory()->create(['default_language_id' => $spanish->id]);

        $service = app(EditorialLanguage::class);

        $this->assertSame('es', $service->resolveCode($location, null));
    }

    public function test_application_default_language_is_used_when_location_is_not_available(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => false]);
        Language::factory()->create(['code' => 'fr', 'is_default' => true]);

        $service = app(EditorialLanguage::class);

        $this->assertSame('fr', $service->resolveCode(null, null));
    }

    public function test_fallback_to_config_locale_or_en_works(): void
    {
        config()->set('app.locale', 'es');
        $service = app(EditorialLanguage::class);

        $this->assertSame('es', $service->resolveCode(null, null));

        config()->set('app.locale', '');
        $this->assertSame('en', $service->resolveCode(null, null));
    }
}
