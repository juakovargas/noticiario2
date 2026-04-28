<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\Script;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ScriptProductionMetadataController extends Controller
{
    private const ALLOWED_PRODUCTION_STATUSES = [
        'draft',
        'metadata_pending',
        'ready_for_review',
        'approved',
        'ready_for_production',
        'in_audio',
        'in_video',
        'ready_to_publish',
        'published',
        'archived',
    ];

    private const TARGET_PLATFORMS = [
        'youtube_shorts',
        'tiktok',
        'instagram_reels',
        'facebook',
        'x_twitter',
        'youtube',
        'website',
    ];

    public function edit(Script $script): Response
    {
        return Inertia::render('Editor/Scripts/ProductionEdit', [
            'script' => [
                'id' => $script->id,
                'title' => $script->title,
                'production_name' => $script->production_name,
                'final_title' => $script->final_title,
                'public_description' => $script->public_description,
                'short_description' => $script->short_description,
                'hashtags' => $script->hashtags ?? [],
                'social_copy' => $script->social_copy,
                'target_platforms' => $script->target_platforms ?? [],
                'seo_title' => $script->seo_title,
                'seo_description' => $script->seo_description,
                'production_status' => $script->production_status ?? 'draft',
                'ready_for_production_at' => $script->ready_for_production_at?->toDateTimeString(),
            ],
            'productionStatuses' => self::ALLOWED_PRODUCTION_STATUSES,
            'targetPlatforms' => self::TARGET_PLATFORMS,
        ]);
    }

    public function update(Request $request, Script $script): RedirectResponse
    {
        $validated = $request->validate([
            'production_name' => ['nullable', 'string', 'max:255'],
            'final_title' => ['nullable', 'string', 'max:255'],
            'public_description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'hashtags' => ['nullable'],
            'social_copy' => ['nullable', 'string'],
            'target_platforms' => ['nullable', 'array'],
            'target_platforms.*' => ['string', Rule::in(self::TARGET_PLATFORMS)],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'production_status' => ['nullable', 'string', Rule::in(self::ALLOWED_PRODUCTION_STATUSES)],
            'mark_ready_for_production' => ['nullable', 'boolean'],
        ]);

        $hashtags = $this->normalizeHashtags($validated['hashtags'] ?? null);
        $markReady = (bool) ($validated['mark_ready_for_production'] ?? false);

        if ($markReady) {
            if (blank($validated['final_title'] ?? null) && blank($validated['production_name'] ?? null)) {
                return back()->withErrors(['final_title' => 'Final title or production name is required before production.']);
            }

            if (blank($validated['public_description'] ?? null) && blank($validated['short_description'] ?? null)) {
                return back()->withErrors(['public_description' => 'Description is required before production.']);
            }

            if (count($hashtags) === 0) {
                return back()->withErrors(['hashtags' => 'At least one hashtag is required before production.']);
            }

            if (count($validated['target_platforms'] ?? []) === 0) {
                return back()->withErrors(['target_platforms' => 'At least one target platform is required before production.']);
            }
        }

        $script->update([
            'production_name' => $validated['production_name'] ?? null,
            'final_title' => $validated['final_title'] ?? null,
            'public_description' => $validated['public_description'] ?? null,
            'short_description' => $validated['short_description'] ?? null,
            'hashtags' => $hashtags,
            'social_copy' => $validated['social_copy'] ?? null,
            'target_platforms' => $validated['target_platforms'] ?? [],
            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
            'production_status' => $markReady ? 'ready_for_production' : ($validated['production_status'] ?? $script->production_status ?? 'draft'),
            'ready_for_production_at' => $markReady ? now() : $script->ready_for_production_at,
            'ready_for_production_by' => $markReady ? $request->user()?->id : $script->ready_for_production_by,
        ]);

        return back()->with('success', $markReady ? 'Script marked ready for production.' : 'Production metadata saved.');
    }

    private function normalizeHashtags(mixed $hashtags): array
    {
        $raw = [];

        if (is_string($hashtags)) {
            $raw = preg_split('/[,\n]+/', $hashtags) ?: [];
        } elseif (is_array($hashtags)) {
            $raw = $hashtags;
        }

        $normalized = [];
        foreach ($raw as $tag) {
            $clean = str_replace(' ', '', trim((string) $tag));
            if ($clean === '') {
                continue;
            }

            if (! str_starts_with($clean, '#')) {
                $clean = '#'.$clean;
            }

            if (! in_array($clean, $normalized, true)) {
                $normalized[] = $clean;
            }
        }

        return $normalized;
    }
}
