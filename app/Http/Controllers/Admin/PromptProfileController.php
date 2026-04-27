<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromptProfile;
use App\Support\GeneratesUniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PromptProfileController extends Controller
{
    use GeneratesUniqueSlug;

    public function index(): Response
    {
        return Inertia::render('Admin/PromptProfiles/Index', [
            'profiles' => PromptProfile::query()->orderByDesc('is_default')->orderBy('sort_order')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/PromptProfiles/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug(PromptProfile::class, $data['slug'] ?: $data['name']);
        $data = $this->normalizeData($request, $data);

        $profile = PromptProfile::query()->create($data);
        $this->enforceSingleDefault($profile);

        return to_route('admin.prompt-profiles.index')->with('success', 'Prompt profile created successfully.');
    }

    public function show(PromptProfile $promptProfile): Response
    {
        return Inertia::render('Admin/PromptProfiles/Show', ['profile' => $promptProfile]);
    }

    public function edit(PromptProfile $promptProfile): Response
    {
        return Inertia::render('Admin/PromptProfiles/Edit', ['profile' => $promptProfile]);
    }

    public function update(Request $request, PromptProfile $promptProfile): RedirectResponse
    {
        $data = $this->validated($request, $promptProfile);
        $data['slug'] = $this->uniqueSlug(PromptProfile::class, $data['slug'] ?: $data['name'], $promptProfile->id);
        $data = $this->normalizeData($request, $data);

        $promptProfile->update($data);
        $this->enforceSingleDefault($promptProfile);

        return to_route('admin.prompt-profiles.index')->with('success', 'Prompt profile updated successfully.');
    }

    public function destroy(PromptProfile $promptProfile): RedirectResponse
    {
        $promptProfile->delete();

        return to_route('admin.prompt-profiles.index')->with('success', 'Prompt profile deleted successfully.');
    }

    private function validated(Request $request, ?PromptProfile $promptProfile = null): array
    {
        $levelRules = ['nullable', 'integer', 'min:0', 'max:10'];

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('prompt_profiles', 'slug')->ignore($promptProfile?->id)],
            'description' => ['nullable', 'string'],
            'happiness_level' => $levelRules,
            'optimism_level' => $levelRules,
            'seriousness_level' => $levelRules,
            'humor_level' => $levelRules,
            'irony_level' => $levelRules,
            'formality_level' => $levelRules,
            'negativity_tolerance' => $levelRules,
            'controversy_tolerance' => $levelRules,
            'source_strictness_level' => $levelRules,
            'target_audience' => ['nullable', 'string', 'max:255'],
            'presenter_style' => ['nullable', 'string', 'max:255'],
            'forbidden_topics' => ['nullable', 'array'],
            'preferred_topics' => ['nullable', 'array'],
            'style_instructions' => ['nullable', 'string'],
            'fact_checking_instructions' => ['nullable', 'string'],
            'output_instructions' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function normalizeData(Request $request, array $data): array
    {
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_default'] = $request->boolean('is_default', false);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }

    private function enforceSingleDefault(PromptProfile $promptProfile): void
    {
        if (! $promptProfile->is_default) {
            return;
        }

        PromptProfile::query()->whereKeyNot($promptProfile->id)->update(['is_default' => false]);
    }
}
