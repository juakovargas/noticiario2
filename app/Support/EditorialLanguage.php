<?php

namespace App\Support;

use App\Models\Language;
use App\Models\Location;
use Illuminate\Support\Collection;

class EditorialLanguage
{
    public function resolveCode(?Location $location = null, ?string $explicitLanguage = null): string
    {
        $explicitCode = $explicitLanguage ? trim(strtolower($explicitLanguage)) : null;

        if ($explicitCode !== null && $explicitCode !== '') {
            return $explicitCode;
        }

        $locationLanguageCode = $location?->defaultLanguage?->code;
        if ($locationLanguageCode) {
            return strtolower($locationLanguageCode);
        }

        $defaultLanguageCode = $this->defaultLanguage()?->code;
        if ($defaultLanguageCode) {
            return strtolower($defaultLanguageCode);
        }

        $configLocale = config('app.locale');
        if (is_string($configLocale) && $configLocale !== '') {
            return strtolower($configLocale);
        }

        return 'en';
    }

    public function defaultLanguage(): ?Language
    {
        return Language::query()
            ->where('is_default', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->first();
    }

    public function activeLanguageOptions(): Collection
    {
        return Language::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'native_name', 'flag_emoji']);
    }
}
