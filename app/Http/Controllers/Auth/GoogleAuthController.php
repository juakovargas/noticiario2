<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuthRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (! $this->isConfigured()) {
            return to_route('login')->with('error', 'Google login is not configured.');
        }

        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        if (! $this->isConfigured()) {
            return to_route('login')->with('error', 'Google login is not configured.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
            $email = $googleUser->getEmail();

            if (blank($email)) {
                return to_route('login')->with('error', 'Your Google account did not provide an email address.');
            }

            $user = User::query()
                ->where('google_id', $googleUser->getId())
                ->first();

            if ($user && ! $this->canLogin($user)) {
                return to_route('login')->with('error', 'Your account is inactive.');
            }

            if (! $user) {
                $user = User::query()->where('email', $email)->first();

                if ($user && ! $this->canLogin($user)) {
                    return to_route('login')->with('error', 'Your account is inactive.');
                }

                if ($user) {
                    $user->forceFill([
                        'google_id' => $googleUser->getId(),
                        'avatar' => $googleUser->getAvatar() ?: $user->avatar,
                    ])->save();
                }
            }

            if (! $user) {
                $user = $this->createUserFromGoogle($googleUser, $email);
            }

            Auth::login($user);
            $request->session()->regenerate();
            $request->session()->forget('url.intended');

            return redirect()
                ->route(AuthRedirect::routeNameFor($user))
                ->with('success', 'Signed in with Google successfully.');
        } catch (Throwable $exception) {
            report($exception);

            return to_route('login')->with('error', 'Could not sign in with Google.');
        }
    }

    private function createUserFromGoogle(SocialiteUser $googleUser, string $email): User
    {
        $user = User::query()->create([
            'name' => $googleUser->getName() ?: Str::before($email, '@'),
            'email' => $email,
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
            'password' => Hash::make(Str::password(64)),
            'email_verified_at' => $this->googleEmailIsVerified($googleUser) ? now() : null,
            'is_active' => true,
        ]);

        $this->assignLowestPrivilegeRole($user);

        return $user;
    }

    private function assignLowestPrivilegeRole(User $user): void
    {
        $role = Role::query()
            ->where('guard_name', 'web')
            ->where('name', 'viewer')
            ->first();

        $role ??= Role::query()
            ->where('guard_name', 'web')
            ->whereNotIn('name', ['superadmin', 'super-admin', 'admin', 'editor'])
            ->orderBy('id')
            ->first();

        if ($role) {
            $user->assignRole($role);
        }
    }

    private function canLogin(User $user): bool
    {
        if (Schema::hasColumn('users', 'is_active')) {
            return (bool) $user->is_active;
        }

        return true;
    }

    private function googleEmailIsVerified(SocialiteUser $googleUser): bool
    {
        $raw = method_exists($googleUser, 'getRaw') ? $googleUser->getRaw() : [];

        return ($raw['email_verified'] ?? false) === true
            || ($raw['verified_email'] ?? false) === true;
    }

    private function isConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }
}
