<?php

namespace App\Http\Middleware;

use App\Models\Language;
use App\Models\User;
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
        $user = $request->user();
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

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
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
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
