<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Editor\StoreNewsItemRequest;
use App\Http\Requests\Editor\UpdateNewsItemRequest;
use App\Models\Language;
use App\Models\Location;
use App\Models\NewsCategory;
use App\Models\NewsItem;
use App\Models\NewsSource;
use App\Support\GeneratesUniqueSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NewsItemController extends Controller
{
    use GeneratesUniqueSlug;

    public function index(Request $request): Response
    {
        $locale = app()->getLocale();

        $filters = [
            'search' => (string) $request->query('search', ''),
            'status' => (string) $request->query('status', ''),
            'news_source_id' => (string) $request->query('news_source_id', ''),
            'news_category_id' => (string) $request->query('news_category_id', ''),
            'location_id' => (string) $request->query('location_id', ''),
            'language' => (string) $request->query('language', ''),
            'editorial_priority' => (string) $request->query('editorial_priority', ''),
            'published_from' => (string) $request->query('published_from', ''),
            'published_to' => (string) $request->query('published_to', ''),
            'sort' => (string) $request->query('sort', 'published_at'),
            'direction' => (string) $request->query('direction', 'desc'),
        ];

        $allowedSorts = ['title', 'status', 'editorial_priority', 'published_at', 'collected_at', 'created_at'];
        $sort = in_array($filters['sort'], $allowedSorts, true) ? $filters['sort'] : 'published_at';
        $direction = in_array($filters['direction'], ['asc', 'desc'], true) ? $filters['direction'] : 'desc';

        $newsItems = NewsItem::query()
            ->with([
                'source:id,name,type',
                'category:id,name,color',
                'category.translations:id,news_category_id,language_code,name',
                'location:id,name,country_code,type',
            ])
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = $filters['search'];
                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('summary', 'like', "%{$search}%")
                        ->orWhere('body', 'like', "%{$search}%")
                        ->orWhere('author', 'like', "%{$search}%")
                        ->orWhere('source_url', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] !== '', fn (Builder $query) => $query->where('status', $filters['status']))
            ->when($filters['news_source_id'] !== '', fn (Builder $query) => $query->where('news_source_id', $filters['news_source_id']))
            ->when($filters['news_category_id'] !== '', fn (Builder $query) => $query->where('news_category_id', $filters['news_category_id']))
            ->when($filters['location_id'] !== '', fn (Builder $query) => $query->where('location_id', $filters['location_id']))
            ->when($filters['language'] !== '', fn (Builder $query) => $query->where('language', $filters['language']))
            ->when($filters['editorial_priority'] !== '', fn (Builder $query) => $query->where('editorial_priority', $filters['editorial_priority']))
            ->when($filters['published_from'] !== '', fn (Builder $query) => $query->whereDate('published_at', '>=', $filters['published_from']))
            ->when($filters['published_to'] !== '', fn (Builder $query) => $query->whereDate('published_at', '<=', $filters['published_to']))
            ->orderByRaw($sort === 'published_at' ? 'published_at is null asc' : '0 asc')
            ->orderBy($sort, $direction)
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (NewsItem $newsItem) => [
                'id' => $newsItem->id,
                'title' => $newsItem->title,
                'source' => $newsItem->source?->name,
                'source_id' => $newsItem->news_source_id,
                'category' => $newsItem->category?->displayName($locale),
                'category_id' => $newsItem->news_category_id,
                'category_color' => $newsItem->category?->color,
                'location' => $newsItem->location,
                'status' => $newsItem->status,
                'published_at' => $newsItem->published_at?->toDateTimeString(),
                'editorial_priority' => $newsItem->editorial_priority,
            ]);

        return Inertia::render('Editor/NewsItems/Index', [
            'newsItems' => $newsItems,
            'filters' => $filters,
            'statuses' => ['draft', 'collected', 'selected', 'rejected', 'archived'],
            'priorities' => [1, 2, 3, 4, 5],
            'languages' => Language::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->pluck('code'),
            'sources' => NewsSource::query()->orderBy('name')->get(['id', 'name', 'type']),
            'categories' => NewsCategory::query()
                ->with('translations:id,news_category_id,language_code,name')
                ->orderBy('name')
                ->get(['id', 'name', 'color'])
                ->map(fn (NewsCategory $category) => [
                    'id' => $category->id,
                    'display_name' => $category->displayName($locale),
                    'name' => $category->name,
                    'color' => $category->color,
                ]),
            'locations' => Location::query()->orderBy('name')->get(['id', 'name', 'country_code', 'type']),
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
        $locale = app()->getLocale();
        $newsItem->load(['source:id,name', 'category:id,name', 'category.translations:id,news_category_id,language_code,name', 'location:id,name,country_code']);

        return Inertia::render('Editor/NewsItems/Show', [
            'newsItem' => [
                'id' => $newsItem->id,
                'title' => $newsItem->title,
                'summary' => $newsItem->summary,
                'body' => $newsItem->body,
                'source' => $newsItem->source?->name,
                'category' => $newsItem->category?->displayName($locale),
                'location' => $newsItem->location,
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
