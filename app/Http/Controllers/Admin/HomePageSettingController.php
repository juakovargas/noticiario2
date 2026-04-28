<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomePageSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomePageSettingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/HomePageSettings/Index', [
            'settings' => HomePageSetting::query()
                ->orderByRaw('CASE WHEN locale IS NULL THEN 1 ELSE 0 END')
                ->orderBy('locale')
                ->orderByDesc('is_active')
                ->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/HomePageSettings/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        HomePageSetting::query()->create($data);

        return redirect()->route('admin.home-page-settings.index')->with('success', 'Home settings saved');
    }

    public function edit(HomePageSetting $homePageSetting): Response
    {
        return Inertia::render('Admin/HomePageSettings/Edit', [
            'setting' => $homePageSetting,
        ]);
    }

    public function update(Request $request, HomePageSetting $homePageSetting): RedirectResponse
    {
        $homePageSetting->update($this->validated($request));

        return redirect()->route('admin.home-page-settings.index')->with('success', 'Home settings saved');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'locale' => ['nullable', 'string', 'max:10'],
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'hero_badge' => ['nullable', 'string', 'max:255'],
            'primary_button_label' => ['nullable', 'string', 'max:255'],
            'primary_button_url' => ['nullable', 'string', 'max:255'],
            'secondary_button_label' => ['nullable', 'string', 'max:255'],
            'secondary_button_url' => ['nullable', 'string', 'max:255'],
            'show_latest_noticiarios' => ['required', 'boolean'],
            'latest_noticiarios_limit' => ['required', 'integer', 'min:1', 'max:24'],
            'show_world_map_preview' => ['required', 'boolean'],
            'show_platforms_section' => ['required', 'boolean'],
            'platforms' => ['nullable', 'array'],
            'platforms.*' => ['string'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        $data['metadata'] ??= [];

        return $data;
    }
}
