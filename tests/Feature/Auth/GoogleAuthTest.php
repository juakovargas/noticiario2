<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.google.client_id', 'test-google-client-id');
        config()->set('services.google.client_secret', 'test-google-client-secret');
        config()->set('services.google.redirect', 'http://localhost/auth/google/callback');
    }

    public function test_google_redirect_sends_the_user_to_socialite(): void
    {
        $provider = Mockery::mock();

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $provider->shouldReceive('scopes')
            ->once()
            ->with(['openid', 'profile', 'email'])
            ->andReturnSelf();

        $provider->shouldReceive('redirect')
            ->once()
            ->andReturn(redirect()->away('https://accounts.google.com/o/oauth2/v2/auth'));

        $this->get(route('auth.google.redirect'))
            ->assertRedirect('https://accounts.google.com/o/oauth2/v2/auth');
    }

    public function test_google_redirect_requests_no_gmail_scopes(): void
    {
        $provider = Mockery::mock();

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $provider->shouldReceive('scopes')
            ->once()
            ->with(Mockery::on(fn (array $scopes): bool => $scopes === ['openid', 'profile', 'email']
                && ! collect($scopes)->contains(fn (string $scope): bool => str_contains($scope, 'gmail') || str_contains($scope, 'mail.google.com'))))
            ->andReturnSelf();

        $provider->shouldReceive('redirect')
            ->once()
            ->andReturn(redirect()->away('https://accounts.google.com/o/oauth2/v2/auth'));

        $this->get(route('auth.google.redirect'))->assertRedirect();
    }

    public function test_existing_user_with_google_id_can_log_in(): void
    {
        $this->ensureViewerRole();

        $user = User::factory()->create([
            'email' => 'linked@example.com',
            'google_id' => 'google-123',
        ]);
        $user->assignRole('viewer');

        $this->mockGoogleCallbackUser([
            'id' => 'google-123',
            'email' => 'linked@example.com',
        ]);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('viewer.dashboard'))
            ->assertSessionHas('success', 'Signed in with Google successfully.');

        $this->assertAuthenticatedAs($user);
    }

    public function test_existing_user_with_same_email_gets_linked_and_logged_in(): void
    {
        $this->ensureViewerRole();

        $user = User::factory()->create([
            'email' => 'same-email@example.com',
            'google_id' => null,
        ]);
        $user->assignRole('viewer');

        $this->mockGoogleCallbackUser([
            'id' => 'google-456',
            'email' => 'same-email@example.com',
            'avatar' => 'https://example.com/avatar.png',
        ]);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('viewer.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'google_id' => 'google-456',
            'avatar' => 'https://example.com/avatar.png',
        ]);
    }

    public function test_new_google_user_is_created_with_viewer_role(): void
    {
        $this->ensureViewerRole();

        $this->mockGoogleCallbackUser([
            'id' => 'google-new',
            'name' => 'Google Viewer',
            'email' => 'google-viewer@example.com',
        ]);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('viewer.dashboard'));

        $user = User::query()->where('email', 'google-viewer@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasRole('viewer'));
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_new_google_user_is_not_assigned_admin_or_editor(): void
    {
        $this->ensureViewerRole();
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('editor', 'web');

        $this->mockGoogleCallbackUser([
            'id' => 'google-low-privilege',
            'email' => 'low-privilege@example.com',
        ]);

        $this->get(route('auth.google.callback'))->assertRedirect(route('viewer.dashboard'));

        $user = User::query()->where('email', 'low-privilege@example.com')->firstOrFail();

        $this->assertFalse($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('editor'));
        $this->assertTrue($user->hasRole('viewer'));
    }

    public function test_inactive_user_cannot_log_in_through_google(): void
    {
        $this->ensureViewerRole();

        $user = User::factory()->create([
            'email' => 'inactive@example.com',
            'google_id' => 'google-inactive',
            'is_active' => false,
        ]);
        $user->assignRole('viewer');

        $this->mockGoogleCallbackUser([
            'id' => 'google-inactive',
            'email' => 'inactive@example.com',
        ]);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Your account is inactive.');

        $this->assertGuest();
    }

    public function test_google_callback_without_email_returns_controlled_error(): void
    {
        $this->mockGoogleCallbackUser([
            'id' => 'google-no-email',
            'email' => null,
        ]);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Your Google account did not provide an email address.');

        $this->assertGuest();
    }

    public function test_registration_and_login_pages_include_google_buttons(): void
    {
        $loginPage = file_get_contents(resource_path('js/Pages/Auth/Login.tsx'));
        $registerPage = file_get_contents(resource_path('js/Pages/Auth/Register.tsx'));

        $this->assertStringContainsString('Continue with Google', $loginPage);
        $this->assertStringContainsString("route('auth.google.redirect')", $loginPage);
        $this->assertStringContainsString('Sign up with Google', $registerPage);
        $this->assertStringContainsString("route('auth.google.redirect')", $registerPage);
    }

    public function test_admin_dashboard_shows_user_stats(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access', 'dashboard.view']);
        User::factory()->create(['is_active' => false]);
        User::factory()->create(['google_id' => 'google-dashboard']);
        User::factory()->create(['created_at' => now()->subDays(10)]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->where('userStats.total', 4)
                ->where('userStats.active', 3)
                ->where('userStats.inactive', 1)
                ->where('userStats.google', 1)
                ->where('userStats.password', 3)
                ->where('userStats.newLastSevenDays', 3)
            );
    }

    private function mockGoogleCallbackUser(array $overrides = []): void
    {
        $googleUser = (new SocialiteUser())->setRaw([
            'email_verified' => array_key_exists('email_verified', $overrides) ? $overrides['email_verified'] : true,
        ])->map([
            'id' => array_key_exists('id', $overrides) ? $overrides['id'] : 'google-id',
            'name' => array_key_exists('name', $overrides) ? $overrides['name'] : 'Google User',
            'email' => array_key_exists('email', $overrides) ? $overrides['email'] : 'google@example.com',
            'avatar' => array_key_exists('avatar', $overrides) ? $overrides['avatar'] : 'https://example.com/google-avatar.png',
        ]);

        $provider = Mockery::mock();

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $provider->shouldReceive('user')
            ->once()
            ->andReturn($googleUser);
    }

    private function ensureViewerRole(): Role
    {
        $role = Role::findOrCreate('viewer', 'web');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $role;
    }
}
