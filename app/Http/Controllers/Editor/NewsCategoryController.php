<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Editor\StoreNewsCategoryRequest;
use App\Http\Requests\Editor\UpdateNewsCategoryRequest;
use App\Models\NewsCategory;
use App\Support\GeneratesUniqueSlug;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NewsCategoryController extends Controller
{
    use GeneratesUniqueSlug;

    public function index(): Response
    {
        return Inertia::render('Editor/NewsCategories/Index', [
            'newsCategories' => NewsCategory::query()
                ->with('parent:id,name')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(10)
                ->through(fn (NewsCategory $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'parent' => $category->parent?->name,
                    'is_active' => $category->is_active,
                    'sort_order' => $category->sort_order,
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Editor/NewsCategories/Create', [
            'parents' => NewsCategory::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreNewsCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug(NewsCategory::class, $data['slug'] ?: $data['name']);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        NewsCategory::query()->create($data);

        return to_route('editor.news-categories.index')->with('success', 'News category created successfully.');
    }

    public function edit(NewsCategory $newsCategory): Response
    {
        return Inertia::render('Editor/NewsCategories/Edit', [
            'newsCategory' => $newsCategory,
            'parents' => NewsCategory::query()->whereKeyNot($newsCategory->id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateNewsCategoryRequest $request, NewsCategory $newsCategory): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug(NewsCategory::class, $data['slug'] ?: $data['name'], $newsCategory->id);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $newsCategory->update($data);

        return to_route('editor.news-categories.index')->with('success', 'News category updated successfully.');
    }

    public function destroy(NewsCategory $newsCategory): RedirectResponse
    {
        $newsCategory->delete();

        return to_route('editor.news-categories.index')->with('success', 'News category deleted successfully.');
    }
}
