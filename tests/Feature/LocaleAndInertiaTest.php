<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class LocaleAndInertiaTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    public function test_locale_update_stores_spanish_locale_in_session(): void
    {
        $user = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);

        $response = $this->actingAs($user)->post(route('locale.update'), [
            'locale' => 'es',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'es');
    }

    public function test_locale_update_stores_english_locale_in_session(): void
    {
        $user = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);

        $response = $this->actingAs($user)->post(route('locale.update'), [
            'locale' => 'en',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'en');
    }

    public function test_locale_update_with_invalid_locale_has_validation_errors(): void
    {
        $user = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);

        $response = $this->from(route('editor.dashboard'))
            ->actingAs($user)
            ->post(route('locale.update'), ['locale' => 'fr']);

        $response->assertRedirect(route('editor.dashboard'));
        $response->assertSessionHasErrors('locale');
    }

    public function test_inertia_shared_props_include_i18n_locale_and_available_locales(): void
    {
        $user = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);

        $this->actingAs($user)
            ->withSession(['locale' => 'es'])
            ->get(route('editor.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('i18n.locale', 'es')
                ->has('i18n.availableLocales', 2)
                ->where('i18n.availableLocales.0.code', 'en')
                ->where('i18n.availableLocales.1.code', 'es')
            );
    }
}
