<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Editor\StoreNewsItemRequest;
use App\Http\Requests\Editor\UpdateNewsItemRequest;
use App\Models\Location;
use App\Models\NewsCategory;
use App\Models\NewsItem;
use App\Models\NewsSource;
use App\Support\GeneratesUniqueSlug;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NewsItemController extends Controller
{
    use GeneratesUniqueSlug;

    public function index(): Response
    {
        return Inertia::render('Editor/NewsItems/Index', [
            'newsItems' => NewsItem::query()
                ->with(['source:id,name', 'category:id,name', 'location:id,name'])
                ->latest('published_at')
                ->paginate(10)
                ->through(fn (NewsItem $newsItem) => [
                    'id' => $newsItem->id,
                    'title' => $newsItem->title,
                    'source' => $newsItem->source?->name,
                    'category' => $newsItem->category?->name,
                    'location' => $newsItem->location?->name,
                    'status' => $newsItem->status,
                    'published_at' => $newsItem->published_at?->toDateTimeString(),
                    'editorial_priority' => $newsItem->editorial_priority,
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Editor/NewsItems/Create', $this->formOptions());
    }

    public function store(StoreNewsItemRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug(NewsItem::class, $data['slug'] ?: $data['title']);
        $data['is_evergreen'] = $request->boolean('is_evergreen', false);
        $data['editorial_priority'] = $data['editorial_priority'] ?? 3;

        NewsItem::query()->create($data);

        return to_route('editor.news-items.index')->with('success', 'News item created successfully.');
    }

    public function show(NewsItem $newsItem): Response
    {
        $newsItem->load(['source:id,name', 'category:id,name', 'location:id,name']);

        return Inertia::render('Editor/NewsItems/Show', [
            'newsItem' => [
                'id' => $newsItem->id,
                'title' => $newsItem->title,
                'summary' => $newsItem->summary,
                'body' => $newsItem->body,
                'source' => $newsItem->source?->name,
                'category' => $newsItem->category?->name,
                'location' => $newsItem->location?->name,
                'source_url' => $newsItem->source_url,
                'status' => $newsItem->status,
                'published_at' => $newsItem->published_at?->toDateTimeString(),
                'collected_at' => $newsItem->collected_at?->toDateTimeString(),
            ],
        ]);
    }

    public function edit(NewsItem $newsItem): Response
    {
        return Inertia::render('Editor/NewsItems/Edit', [
            'newsItem' => $newsItem,
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateNewsItemRequest $request, NewsItem $newsItem): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug(NewsItem::class, $data['slug'] ?: $data['title'], $newsItem->id);
        $data['is_evergreen'] = $request->boolean('is_evergreen', false);
        $data['editorial_priority'] = $data['editorial_priority'] ?? 3;

        $newsItem->update($data);

        return to_route('editor.news-items.index')->with('success', 'News item updated successfully.');
    }

    public function destroy(NewsItem $newsItem): RedirectResponse
    {
        $newsItem->delete();

        return to_route('editor.news-items.index')->with('success', 'News item deleted successfully.');
    }

    private function formOptions(): array
    {
        return [
            'sources' => NewsSource::query()->orderBy('name')->get(['id', 'name']),
            'categories' => NewsCategory::query()->orderBy('name')->get(['id', 'name']),
            'locations' => Location::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => ['draft', 'collected', 'selected', 'rejected', 'archived'],
        ];
    }
}
