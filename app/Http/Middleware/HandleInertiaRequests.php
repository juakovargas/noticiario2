<?php

namespace App\Http\Middleware;

use App\Models\Language;
use App\Models\User;
use App\Support\Seo\SeoSettingsResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user()?->loadMissing('profileImage');
        $impersonatorId = $request->session()->get('impersonator_id');
        $impersonator = $impersonatorId ? User::query()->find($impersonatorId) : null;

        $availableLocales = [
            ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'flag_emoji' => '🇬🇧'],
            ['code' => 'es', 'name' => 'Spanish', 'native_name' => 'Español', 'flag_emoji' => '🇪🇸'],
        ];

        if (Schema::hasTable('languages')) {
            $dbLocales = Language::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['code', 'name', 'native_name', 'flag_emoji'])
                ->map(fn (Language $language) => [
                    'code' => $language->code,
                    'name' => $language->name,
                    'native_name' => $language->native_name,
                    'flag_emoji' => $language->flag_emoji,
                ])
                ->values()
                ->all();

            if (! empty($dbLocales)) {
                $availableLocales = $dbLocales;
            }
        }

        $seoProps = app(SeoSettingsResolver::class)->safeSeoProps();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                    'preferred_locale' => $user->preferred_locale,
                    'timezone' => $user->timezone,
                    'date_format' => $user->date_format,
                    'time_format' => $user->time_format,
                    'avatar_url' => $user->avatar_url,
                    'avatarUrl' => $user->avatar_url,
                    'initials' => $user->initials,
                    'profile_image_id' => $user->profile_image_id,
                ] : null,
            ],
            'impersonation' => [
                'active' => (bool) $impersonatorId,
                'impersonator_id' => $impersonatorId,
                'impersonator_name' => $impersonator?->name,
                'current_user_name' => $user?->name,
            ],
            'i18n' => [
                'locale' => app()->getLocale(),
                'availableLocales' => $availableLocales,
            ],
            'seo' => $seoProps,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
