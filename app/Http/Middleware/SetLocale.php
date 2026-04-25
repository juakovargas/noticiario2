<?php

namespace App\Http\Middleware;

use Closure;
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
        $defaultLocale = config('app.locale', 'en');
        $supportedLocales = ['en', 'es'];

        if (Schema::hasTable('languages')) {
            $codes = \App\Models\Language::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->pluck('code')
                ->values()
                ->all();

            if (! empty($codes)) {
                $supportedLocales = $codes;
            }
        }

        $locale = $request->session()->get('locale', $defaultLocale);

        if (! in_array($locale, $supportedLocales, true)) {
            $locale = $defaultLocale;
        }

        App::setLocale($locale);

        return $next($request);
    }
}
