<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\NewsItem;
use App\Models\Script;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ScriptBuilderController extends Controller
{
    public function create(Edition $edition): Response
    {
        $locale = app()->getLocale();

        $edition->load([
            'location:id,name,country_code',
            'newsItems' => fn ($query) => $query
                ->with([
                    'source:id,name',
                    'category:id,name',
                    'category.translations:id,news_category_id,language_code,name',
                    'location:id,name,country_code',
                ])
                ->orderBy('edition_news_item.sort_order'),
            'scripts:id,edition_id,title,status,language,estimated_duration_seconds',
        ]);

        $includedNewsItems = $edition->newsItems
            ->filter(fn (NewsItem $item) => (bool) $item->pivot->included_in_script)
            ->values();

        $transitions = ['First', 'Next', 'Also', 'Finally'];

        $body = $includedNewsItems
            ->values()
            ->map(function (NewsItem $item, int $index) use ($transitions): string {
                $prefix = $transitions[min($index, count($transitions) - 1)];
                $angle = $item->pivot->editorial_angle ? trim($item->pivot->editorial_angle).'.' : '';
                $summary = $item->summary ? trim($item->summary) : '';

                return trim($prefix.', '.$item->title.'. '.$angle.' '.$summary);
            })
            ->implode("\n\n");

        return Inertia::render('Editor/Scripts/Builder', [
            'edition' => [
                'id' => $edition->id,
                'title' => $edition->title,
                'edition_type' => $edition->edition_type,
                'location' => $edition->location,
                'scheduled_for' => $edition->scheduled_for?->toDateTimeString(),
                'language' => $edition->language,
                'status' => $edition->status,
            ],
            'selectedNewsItems' => $includedNewsItems->map(fn (NewsItem $item) => [
                'id' => $item->id,
                'sort_order' => $item->pivot->sort_order,
                'title' => $item->title,
                'summary' => $item->summary,
                'editorial_angle' => $item->pivot->editorial_angle,
                'source' => $item->source?->name,
                'category' => $item->category?->displayName($locale),
                'location' => $item->location,
                'included_in_script' => (bool) $item->pivot->included_in_script,
            ])->values(),
            'existingScripts' => $edition->scripts->map(fn (Script $script) => [
                'id' => $script->id,
                'title' => $script->title,
                'status' => $script->status,
                'language' => $script->language,
                'estimated_duration_seconds' => $script->estimated_duration_seconds,
            ])->values(),
            'prefill' => [
                'title' => 'Draft script for '.$edition->title,
                'status' => 'draft',
                'language' => $edition->language,
                'intro' => 'Hello. This is '.$edition->title.'. These are the main stories.',
                'body' => $body,
                'outro' => 'That is all for this edition. Follow us for more updates.',
                'estimated_duration_seconds' => null,
            ],
        ]);
    }

    public function store(Request $request, Edition $edition): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50', Rule::in(['draft', 'review'])],
            'language' => ['nullable', 'string', 'max:10'],
            'intro' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'outro' => ['nullable', 'string'],
            'estimated_duration_seconds' => ['nullable', 'integer', 'min:1'],
        ]);

        $newsItemIds = $edition->newsItems()
            ->wherePivot('included_in_script', true)
            ->orderBy('edition_news_item.sort_order')
            ->pluck('news_items.id')
            ->values()
            ->all();

        $script = Script::query()->create([
            'edition_id' => $edition->id,
            'title' => $data['title'],
            'status' => $data['status'] ?: 'draft',
            'language' => $data['language'] ?: $edition->language,
            'intro' => $data['intro'] ?? null,
            'body' => $data['body'] ?? null,
            'outro' => $data['outro'] ?? null,
            'estimated_duration_seconds' => $data['estimated_duration_seconds'] ?? null,
            'metadata' => [
                'created_from' => 'manual_builder',
                'news_item_ids' => $newsItemIds,
                'template_version' => 'manual_v1',
            ],
        ]);

        return to_route('editor.scripts.show', $script)->with('success', 'Draft script created successfully.');
    }
}
