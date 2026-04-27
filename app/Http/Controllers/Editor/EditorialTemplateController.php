<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\EditorialTemplate;
use App\Models\Language;
use App\Models\Location;
use App\Support\GeneratesUniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EditorialTemplateController extends Controller
{
    use GeneratesUniqueSlug;

    public function index(): Response
    {
        return Inertia::render('Editor/EditorialTemplates/Index', [
            'templates' => EditorialTemplate::query()
                ->with(['language:id,code,name,native_name,flag_emoji', 'location:id,name,country_code'])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(15)
                ->through(fn (EditorialTemplate $template) => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'slug' => $template->slug,
                    'language' => $template->language,
                    'location' => $template->location,
                    'edition_type' => $template->edition_type,
                    'target_duration_seconds' => $template->target_duration_seconds,
                    'is_active' => $template->is_active,
                    'sort_order' => $template->sort_order,
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Editor/EditorialTemplates/Create', $this->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug(EditorialTemplate::class, $data['slug'] ?: $data['name']);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        EditorialTemplate::query()->create($data);

        return to_route('editor.editorial-templates.index')->with('success', 'Editorial template created successfully.');
    }

    public function show(EditorialTemplate $editorialTemplate): Response
    {
        $editorialTemplate->load(['language:id,code,name,native_name,flag_emoji', 'location:id,name,country_code']);

        return Inertia::render('Editor/EditorialTemplates/Show', [
            'template' => $editorialTemplate,
        ]);
    }

    public function edit(EditorialTemplate $editorialTemplate): Response
    {
        return Inertia::render('Editor/EditorialTemplates/Edit', [
            'template' => $editorialTemplate,
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, EditorialTemplate $editorialTemplate): RedirectResponse
    {
        $data = $this->validated($request, $editorialTemplate);
        $data['slug'] = $this->uniqueSlug(EditorialTemplate::class, $data['slug'] ?: $data['name'], $editorialTemplate->id);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $editorialTemplate->update($data);

        return to_route('editor.editorial-templates.index')->with('success', 'Editorial template updated successfully.');
    }

    public function destroy(EditorialTemplate $editorialTemplate): RedirectResponse
    {
        $editorialTemplate->delete();

        return to_route('editor.editorial-templates.index')->with('success', 'Editorial template deleted successfully.');
    }

    private function validated(Request $request, ?EditorialTemplate $editorialTemplate = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('editorial_templates', 'slug')->ignore($editorialTemplate?->id)],
            'description' => ['nullable', 'string'],
            'language_id' => ['nullable', 'exists:languages,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'edition_type' => ['nullable', 'string', 'max:50'],
            'target_duration_seconds' => ['nullable', 'integer', 'min:1'],
            'intro_template' => ['nullable', 'string'],
            'body_template' => ['nullable', 'string'],
            'outro_template' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'metadata' => ['nullable', 'array'],
        ]);
    }

    private function formOptions(): array
    {
        return [
            'languages' => Language::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'code', 'name', 'native_name', 'flag_emoji']),
            'locations' => Location::query()->orderBy('name')->get(['id', 'name', 'country_code']),
            'editionTypes' => ['morning', 'afternoon', 'night', 'special'],
        ];
    }
}
