<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLanguageRequest;
use App\Http\Requests\Admin\UpdateLanguageRequest;
use App\Models\Language;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LanguageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Languages/Index', [
            'languages' => Language::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(15)
                ->through(fn (Language $language) => [
                    'id' => $language->id,
                    'name' => $language->name,
                    'native_name' => $language->native_name,
                    'code' => $language->code,
                    'flag_emoji' => $language->flag_emoji,
                    'is_active' => $language->is_active,
                    'is_default' => $language->is_default,
                    'sort_order' => $language->sort_order,
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Languages/Create');
    }

    public function store(StoreLanguageRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_default'] = $request->boolean('is_default', false);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $language = Language::query()->create($data);

        if ($language->is_default) {
            Language::query()->whereKeyNot($language->id)->update(['is_default' => false]);
        }

        if (! Language::query()->where('is_default', true)->exists()) {
            $language->update(['is_default' => true]);
        }

        return to_route('admin.languages.index')->with('success', 'Language created successfully.');
    }

    public function edit(Language $language): Response
    {
        return Inertia::render('Admin/Languages/Edit', [
            'language' => $language,
        ]);
    }

    public function update(UpdateLanguageRequest $request, Language $language): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_default'] = $request->boolean('is_default', false);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $language->update($data);

        if ($language->is_default) {
            Language::query()->whereKeyNot($language->id)->update(['is_default' => false]);
        } elseif (! Language::query()->where('is_default', true)->whereKeyNot($language->id)->exists()) {
            $language->update(['is_default' => true]);
        }

        return to_route('admin.languages.index')->with('success', 'Language updated successfully.');
    }

    public function destroy(Language $language): RedirectResponse
    {
        if ($language->is_default) {
            return back()->with('error', 'Default language cannot be deleted.');
        }

        if (app()->getLocale() === $language->code) {
            return back()->with('error', 'Current app locale cannot be deleted.');
        }

        $language->delete();

        return to_route('admin.languages.index')->with('success', 'Language deleted successfully.');
    }
}
