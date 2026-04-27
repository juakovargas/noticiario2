<?php

namespace App\Support\Seo;

use App\Models\SeoSetting;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SeoSettingsResolver
{
    private ?SeoSetting $cached = null;

    private bool $resolved = false;

    public function resolve(bool $createIfMissing = false): ?SeoSetting
    {
        if ($this->resolved) {
            return $this->cached;
        }

        $this->resolved = true;

        try {
            if (! Schema::hasTable('seo_settings')) {
                return null;
            }

            $this->cached = $createIfMissing ? SeoSetting::current() : SeoSetting::query()->first();

            return $this->cached;
        } catch (Throwable) {
            return null;
        }
    }

    public function safeSeoProps(): ?array
    {
        $settings = $this->resolve();

        if (! $settings) {
            return null;
        }

        return [
            'site_name' => $settings->site_name,
            'default_title' => $settings->default_title,
            'title_suffix' => $settings->title_suffix,
            'default_description' => $settings->default_description,
            'enable_indexing' => $settings->enable_indexing,
        ];
    }
}
