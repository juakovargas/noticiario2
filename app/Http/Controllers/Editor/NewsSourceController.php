<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Editor\StoreNewsSourceRequest;
use App\Http\Requests\Editor\UpdateNewsSourceRequest;
use App\Models\Location;
use App\Models\NewsCategory;
use App\Models\NewsSource;
use App\Support\GeneratesUniqueSlug;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NewsSourceController extends Controller
{
    use GeneratesUniqueSlug;

    public function index(): Response
    {
        return Inertia::render('Editor/NewsSources/Index', [
            'newsSources' => NewsSource::query()
                ->with(['defaultCategory:id,name', 'defaultLocation:id,name'])
                ->orderBy('name')
                ->paginate(10)
                ->through(fn (NewsSource $source) => [
                    'id' => $source->id,
                    'name' => $source->name,
                    'type' => $source->type,
                    'url' => $source->url,
                    'feed_url' => $source->feed_url,
                    'language' => $source->language,
                    'is_active' => $source->is_active,
                    'is_demo' => $source->is_demo,
                    'trust_level' => $source->trust_level,
                    'default_category' => $source->defaultCategory?->name,
                    'default_location' => $source->defaultLocation?->name,
                    'last_checked_at' => $source->last_checked_at?->toDateTimeString(),
                    'last_imported_at' => $source->last_imported_at?->toDateTimeString(),
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Editor/NewsSources/Create', [
            'types' => ['rss', 'website', 'manual', 'api'],
            ...$this->sourceDefaults(),
        ]);
    }

    public function store(StoreNewsSourceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug(NewsSource::class, $data['slug'] ?: $data['name']);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_demo'] = $request->boolean('is_demo', false);
        $data['trust_level'] = $data['trust_level'] ?? 3;

        NewsSource::query()->create($data);

        return to_route('editor.news-sources.index')->with('success', 'News source created successfully.');
    }

    public function edit(NewsSource $newsSource): Response
    {
        return Inertia::render('Editor/NewsSources/Edit', [
            'newsSource' => $newsSource->load(['defaultCategory:id,name', 'defaultLocation:id,name']),
            'types' => ['rss', 'website', 'manual', 'api'],
            ...$this->sourceDefaults(),
        ]);
    }

    public function update(UpdateNewsSourceRequest $request, NewsSource $newsSource): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug(NewsSource::class, $data['slug'] ?: $data['name'], $newsSource->id);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_demo'] = $request->boolean('is_demo', false);
        $data['trust_level'] = $data['trust_level'] ?? 3;

        $newsSource->update($data);

        return to_route('editor.news-sources.index')->with('success', 'News source updated successfully.');
    }

    public function destroy(NewsSource $newsSource): RedirectResponse
    {
        $newsSource->delete();

        return to_route('editor.news-sources.index')->with('success', 'News source deleted successfully.');
    }

    private function sourceDefaults(): array
    {
        return [
            'categories' => NewsCategory::query()->orderBy('name')->get(['id', 'name']),
            'locations' => Location::query()->orderBy('name')->get(['id', 'name']),
        ];
    }
}
