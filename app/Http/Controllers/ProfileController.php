<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Language;
use App\Services\UserAvatarService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(private readonly UserAvatarService $avatarService)
    {
    }

    public function edit(Request $request): Response
    {
        $panel = $request->string('panel')->value();

        if (! in_array($panel, ['admin', 'editor', 'viewer'], true)) {
            $panel = null;
        }

        if (! $panel) {
            $user = $request->user();
            $panel = $user?->can('admin.access') ? 'admin' : ($user?->can('editor.access') ? 'editor' : 'viewer');
        }

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
            'panel' => $panel,
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->safe()->except(['avatar', 'remove_avatar', 'panel']);

        if ($request->boolean('remove_avatar')) {
            $this->avatarService->deleteAvatar($user);
            $data['avatar_path'] = null;
        }

        if ($request->hasFile('avatar')) {
            $data['avatar_path'] = $this->avatarService->replaceAvatar($user, $request->file('avatar'));
        }

        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($user->isDirty('preferred_locale') && filled($user->preferred_locale)) {
            $request->session()->put('locale', $user->preferred_locale);
        }

        if ($user->isDirty('preferred_locale') && blank($user->preferred_locale)) {
            $request->session()->forget('locale');
        }

        $user->save();

        return Redirect::route('profile.edit', ['panel' => $request->input('panel')])->with('success', 'Profile updated');
    }

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
