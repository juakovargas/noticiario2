<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $locales = ['en', 'es'];

        if (Schema::hasTable('languages')) {
            $codes = \App\Models\Language::query()->where('is_active', true)->pluck('code')->all();
            if (! empty($codes)) {
                $locales = $codes;
            }
        }

        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in($locales)],
        ]);

        $request->session()->put('locale', $validated['locale']);

        if ($request->user() && Schema::hasColumn('users', 'preferred_locale')) {
            $request->user()->forceFill([
                'preferred_locale' => $validated['locale'],
            ])->save();
        }

        return back();
    }
}
