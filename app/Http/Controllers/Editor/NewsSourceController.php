<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Editor\StoreNewsSourceRequest;
use App\Http\Requests\Editor\UpdateNewsSourceRequest;
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
                    'trust_level' => $source->trust_level,
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Editor/NewsSources/Create', [
            'types' => ['rss', 'website', 'manual', 'api'],
        ]);
    }

    public function store(StoreNewsSourceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug(NewsSource::class, $data['slug'] ?: $data['name']);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['trust_level'] = $data['trust_level'] ?? 3;

        NewsSource::query()->create($data);

        return to_route('editor.news-sources.index')->with('success', 'News source created successfully.');
    }

    public function edit(NewsSource $newsSource): Response
    {
        return Inertia::render('Editor/NewsSources/Edit', [
            'newsSource' => $newsSource,
            'types' => ['rss', 'website', 'manual', 'api'],
        ]);
    }

    public function update(UpdateNewsSourceRequest $request, NewsSource $newsSource): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug(NewsSource::class, $data['slug'] ?: $data['name'], $newsSource->id);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['trust_level'] = $data['trust_level'] ?? 3;

        $newsSource->update($data);

        return to_route('editor.news-sources.index')->with('success', 'News source updated successfully.');
    }

    public function destroy(NewsSource $newsSource): RedirectResponse
    {
        $newsSource->delete();

        return to_route('editor.news-sources.index')->with('success', 'News source deleted successfully.');
    }
}
