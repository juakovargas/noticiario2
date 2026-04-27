<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Seo\SeoSettingsResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SeoSettingController extends Controller
{
    public function __construct(private readonly SeoSettingsResolver $resolver)
    {
    }

    public function edit(): Response
    {
        $settings = $this->resolver->resolve(createIfMissing: true);

        return Inertia::render('Admin/SeoSettings/Edit', [
            'seoSettings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name' => ['nullable', 'string', 'max:255'],
            'default_title' => ['nullable', 'string', 'max:255'],
            'title_suffix' => ['nullable', 'string', 'max:255'],
            'default_description' => ['nullable', 'string', 'max:500'],
            'default_keywords' => ['nullable', 'string', 'max:500'],
            'canonical_base_url' => ['nullable', 'url', 'max:255'],
            'default_robots' => ['required', 'string', 'max:100'],
            'default_og_image' => ['nullable', 'string', 'max:255'],
            'default_twitter_card' => ['nullable', 'string', 'max:100'],
            'google_site_verification' => ['nullable', 'string', 'max:255'],
            'bing_site_verification' => ['nullable', 'string', 'max:255'],
            'google_tag_manager_id' => ['nullable', 'string', 'max:50'],
            'google_analytics_id' => ['nullable', 'string', 'max:50'],
            'microsoft_clarity_id' => ['nullable', 'string', 'max:100'],
            'meta_pixel_id' => ['nullable', 'string', 'max:100'],
            'tiktok_pixel_id' => ['nullable', 'string', 'max:100'],
            'custom_head_scripts' => ['nullable', 'string'],
            'custom_body_start_scripts' => ['nullable', 'string'],
            'custom_body_end_scripts' => ['nullable', 'string'],
            'enable_tracking' => ['boolean'],
            'enable_custom_scripts' => ['boolean'],
            'enable_indexing' => ['boolean'],
        ]);

        $settings = $this->resolver->resolve(createIfMissing: true);

        abort_unless($settings !== null, 500, 'SEO settings table is not available.');

        $settings->update([
            ...$validated,
            'enable_tracking' => $request->boolean('enable_tracking'),
            'enable_custom_scripts' => $request->boolean('enable_custom_scripts'),
            'enable_indexing' => $request->boolean('enable_indexing', true),
        ]);

        return back()->with('success', 'Settings saved successfully');
    }
}
