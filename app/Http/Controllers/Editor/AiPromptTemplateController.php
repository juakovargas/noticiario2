<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\AiPromptTemplate;
use App\Models\Language;
use App\Models\Location;
use App\Models\NewsCategory;
use App\Support\GeneratesUniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AiPromptTemplateController extends Controller
{
    use GeneratesUniqueSlug;

    public function index(): Response
    {
        return Inertia::render('Editor/AiPromptTemplates/Index', [
            'templates' => AiPromptTemplate::query()
                ->with(['language:id,code,name,native_name,flag_emoji', 'location:id,name', 'newsCategory:id,name'])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(15),
            'types' => $this->types(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Editor/AiPromptTemplates/Create', $this->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug(AiPromptTemplate::class, $data['slug'] ?: $data['name']);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        AiPromptTemplate::query()->create($data);

        return to_route('editor.ai-prompt-templates.index')->with('success', 'Prompt template created successfully.');
    }

    public function show(AiPromptTemplate $aiPromptTemplate): Response
    {
        $aiPromptTemplate->load(['language:id,code,name,native_name,flag_emoji', 'location:id,name', 'newsCategory:id,name']);

        return Inertia::render('Editor/AiPromptTemplates/Show', ['template' => $aiPromptTemplate]);
    }

    public function edit(AiPromptTemplate $aiPromptTemplate): Response
    {
        return Inertia::render('Editor/AiPromptTemplates/Edit', ['template' => $aiPromptTemplate, ...$this->formOptions()]);
    }

    public function update(Request $request, AiPromptTemplate $aiPromptTemplate): RedirectResponse
    {
        $data = $this->validated($request, $aiPromptTemplate);
        $data['slug'] = $this->uniqueSlug(AiPromptTemplate::class, $data['slug'] ?: $data['name'], $aiPromptTemplate->id);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $aiPromptTemplate->update($data);

        return to_route('editor.ai-prompt-templates.index')->with('success', 'Prompt template updated successfully.');
    }

    public function destroy(AiPromptTemplate $aiPromptTemplate): RedirectResponse
    {
        $aiPromptTemplate->delete();

        return to_route('editor.ai-prompt-templates.index')->with('success', 'Prompt template deleted successfully.');
    }

    private function validated(Request $request, ?AiPromptTemplate $template = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('ai_prompt_templates', 'slug')->ignore($template?->id)],
            'type' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'system_prompt' => ['nullable', 'string'],
            'user_prompt' => ['required', 'string'],
            'language_id' => ['nullable', 'exists:languages,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'news_category_id' => ['nullable', 'exists:news_categories,id'],
            'edition_type' => ['nullable', 'string', 'max:50'],
            'expected_output_format' => ['required', 'string', 'max:50'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'metadata' => ['nullable', 'array'],
        ]);
    }

    private function formOptions(): array
    {
        return [
            'languages' => Language::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'code', 'name', 'native_name', 'flag_emoji']),
            'locations' => Location::query()->orderBy('name')->get(['id', 'name']),
            'categories' => NewsCategory::query()->orderBy('name')->get(['id', 'name']),
            'types' => $this->types(),
            'outputFormats' => ['text', 'json'],
        ];
    }

    /** @return string[] */
    private function types(): array
    {
        return ['editorial_research', 'news_selection', 'script_generation', 'summary', 'headline', 'social_copy'];
    }
}
