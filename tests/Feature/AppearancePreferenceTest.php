<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class AppearancePreferenceTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    public function test_authenticated_user_can_update_appearance_to_light_dark_and_system(): void
    {
        $user = User::factory()->create();

        foreach (['light', 'dark', 'system'] as $appearance) {
            $this->actingAs($user)->patch(route('preferences.appearance.update'), [
                'appearance' => $appearance,
            ])->assertRedirect();

            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'appearance' => $appearance,
            ]);
        }
    }

    public function test_invalid_appearance_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('preferences.appearance.update'), [
            'appearance' => 'blue',
        ])->assertSessionHasErrors('appearance');
    }

    public function test_guest_cannot_update_appearance(): void
    {
        $this->patch(route('preferences.appearance.update'), ['appearance' => 'dark'])
            ->assertRedirect(route('login'));
    }

    public function test_inertia_shared_auth_user_includes_appearance(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $editor->update(['appearance' => 'dark']);

        $this->actingAs($editor)
            ->get(route('editor.dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('auth.user.appearance', 'dark'));
    }
}
