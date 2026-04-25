<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Editor\StoreEditionNewsItemRequest;
use App\Http\Requests\Editor\UpdateEditionNewsItemRequest;
use App\Models\Edition;
use App\Models\NewsItem;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EditionNewsItemController extends Controller
{
    public function index(Edition $edition): Response
    {
        $edition->load([
            'newsItems' => fn ($query) => $query
                ->with(['source:id,name', 'category:id,name', 'location:id,name'])
                ->orderBy('edition_news_item.sort_order'),
        ]);

        return Inertia::render('Editor/Editions/NewsItems/Index', [
            'edition' => [
                'id' => $edition->id,
                'title' => $edition->title,
            ],
            'newsItems' => $edition->newsItems->map(fn (NewsItem $item) => [
                'id' => $item->id,
                'title' => $item->title,
                'source' => $item->source?->name,
                'category' => $item->category?->name,
                'location' => $item->location?->name,
                'sort_order' => $item->pivot->sort_order,
                'editorial_angle' => $item->pivot->editorial_angle,
                'included_in_script' => (bool) $item->pivot->included_in_script,
            ])->values(),
        ]);
    }

    public function store(StoreEditionNewsItemRequest $request, Edition $edition): RedirectResponse
    {
        $data = $request->validated();

        if ($edition->newsItems()->where('news_item_id', $data['news_item_id'])->exists()) {
            return back()->withErrors([
                'news_item_id' => 'This news item is already attached to the edition.',
            ]);
        }

        $edition->newsItems()->attach($data['news_item_id'], [
            'sort_order' => $data['sort_order'] ?? 0,
            'editorial_angle' => $data['editorial_angle'] ?? null,
            'included_in_script' => $data['included_in_script'],
        ]);

        return back()->with('success', 'News item added to edition successfully.');
    }

    public function update(UpdateEditionNewsItemRequest $request, Edition $edition, NewsItem $newsItem): RedirectResponse
    {
        if (! $edition->newsItems()->where('news_item_id', $newsItem->id)->exists()) {
            abort(404);
        }

        $data = $request->validated();

        $edition->newsItems()->updateExistingPivot($newsItem->id, [
            'sort_order' => $data['sort_order'] ?? 0,
            'editorial_angle' => $data['editorial_angle'] ?? null,
            'included_in_script' => $data['included_in_script'],
        ]);

        return back()->with('success', 'Edition news item updated successfully.');
    }

    public function destroy(Edition $edition, NewsItem $newsItem): RedirectResponse
    {
        $edition->newsItems()->detach($newsItem->id);

        return back()->with('success', 'News item removed from edition successfully.');
    }
}
