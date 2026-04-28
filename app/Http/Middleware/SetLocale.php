<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $configLocale = config('app.locale', 'en');
        $supportedLocales = ['en', 'es', 'fr'];
        $defaultActiveLanguage = null;

        if (Schema::hasTable('languages')) {
            try {
                $activeLanguages = \App\Models\Language::query()
                    ->where('is_active', true)
                    ->orderByDesc('is_default')
                    ->orderBy('sort_order')
                    ->get(['code', 'is_default']);

                if ($activeLanguages->isNotEmpty()) {
                    $supportedLocales = $activeLanguages->pluck('code')->values()->all();
                    $defaultActiveLanguage = $activeLanguages->firstWhere('is_default', true)?->code ?? $activeLanguages->first()?->code;
                }
            } catch (QueryException) {
                // Ignore transient migration states and fallback to static locales.
            }
        }

        $locale = $request->session()->get('locale');

        if (! $locale && $request->user()?->preferred_locale) {
            $locale = $request->user()->preferred_locale;
        }

        if (! is_string($locale) || ! in_array($locale, $supportedLocales, true)) {
            $fallback = $defaultActiveLanguage ?? $configLocale ?? 'en';
            $locale = in_array($fallback, $supportedLocales, true) ? $fallback : 'en';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
