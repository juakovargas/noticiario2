<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Language;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $locales = [
            ['code' => 'en', 'name' => 'English'],
            ['code' => 'es', 'name' => 'Español'],
        ];

        if (Schema::hasTable('languages')) {
            $dbLocales = Language::query()->where('is_active', true)->orderBy('sort_order')->get(['code', 'name']);
            if ($dbLocales->isNotEmpty()) {
                $locales = $dbLocales->map(fn (Language $language) => ['code' => $language->code, 'name' => $language->name])->values()->all();
            }
        }

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'locales' => $locales,
            'dateFormatOptions' => ['locale_default', 'dd/mm/yyyy', 'yyyy-mm-dd', 'mm/dd/yyyy'],
            'timeFormatOptions' => ['24h', '12h'],
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->safe()->except('avatar');

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $data['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($user->isDirty('preferred_locale') && filled($user->preferred_locale)) {
            $request->session()->put('locale', $user->preferred_locale);
        }

        $user->save();

        return Redirect::route('profile.edit')->with('success', 'Profile updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
