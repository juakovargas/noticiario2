<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\HomePageSetting;
use App\Models\Script;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        $locale = app()->getLocale();
        $setting = $this->resolveSetting($locale);

        $latestScripts = [];
        if ($setting?->show_latest_noticiarios) {
            $latestScripts = Script::query()
                ->with(['edition.location'])
                ->whereNull('deleted_at')
                ->where(function ($query): void {
                    $query->whereIn('production_status', ['ready_for_production', 'ready_to_publish', 'published'])
                        ->orWhereIn('status', ['approved', 'completed']);
                })
                ->latest('updated_at')
                ->limit($setting->latest_noticiarios_limit ?: 6)
                ->get()
                ->map(fn (Script $script) => [
                    'id' => $script->id,
                    'title' => $script->final_title ?: $script->title,
                    'description' => $script->short_description ?: $script->public_description,
                    'hashtags' => $script->hashtags ?? [],
                    'target_platforms' => $script->target_platforms ?? [],
                    'production_status' => $script->production_status,
                    'status' => $script->status,
                    'updated_at' => optional($script->updated_at)?->toIso8601String(),
                    'location' => $script->edition?->location?->name,
                    'category' => null,
                ])
                ->values();
        }

        return Inertia::render('Public/Home', [
            'homeSetting' => $setting,
            'latestNoticiarios' => $latestScripts,
        ]);
    }

    private function resolveSetting(string $locale): HomePageSetting
    {
        return HomePageSetting::query()
            ->where('is_active', true)
            ->where(function ($query) use ($locale): void {
                $query->where('locale', $locale)->orWhereNull('locale')->orWhere('locale', 'en');
            })
            ->orderByRaw("CASE WHEN locale = ? THEN 0 WHEN locale IS NULL THEN 1 WHEN locale = 'en' THEN 2 ELSE 3 END", [$locale])
            ->first() ?? new HomePageSetting([
                'title' => 'Noticiario',
                'subtitle' => null,
                'description' => null,
                'hero_badge' => null,
                'primary_button_label' => 'Login',
                'primary_button_url' => '/login',
                'secondary_button_label' => null,
                'secondary_button_url' => null,
                'show_latest_noticiarios' => true,
                'latest_noticiarios_limit' => 6,
                'show_world_map_preview' => true,
                'show_platforms_section' => true,
                'platforms' => [],
                'is_active' => true,
            ]);
    }
}
