<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiProvider;
use App\Support\GeneratesUniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AiProviderController extends Controller
{
    use GeneratesUniqueSlug;

    public function index(): Response
    {
        return Inertia::render('Admin/AiProviders/Index', [
            'providers' => AiProvider::query()->orderByDesc('is_default')->orderBy('name')->paginate(15),
            'providerTypes' => $this->providerTypes(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/AiProviders/Create', ['providerTypes' => $this->providerTypes()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug(AiProvider::class, $data['slug'] ?: $data['name']);
        $data = $this->withBooleanValues($request, $data);

        $provider = AiProvider::query()->create($data);

        $this->enforceDefault($provider, $provider->is_default);

        return to_route('admin.ai-providers.index')->with('success', 'AI provider created successfully.');
    }

    public function show(AiProvider $aiProvider): Response
    {
        return Inertia::render('Admin/AiProviders/Show', ['provider' => $aiProvider]);
    }

    public function edit(AiProvider $aiProvider): Response
    {
        return Inertia::render('Admin/AiProviders/Edit', ['provider' => $aiProvider, 'providerTypes' => $this->providerTypes()]);
    }

    public function update(Request $request, AiProvider $aiProvider): RedirectResponse
    {
        $data = $this->validated($request, $aiProvider);
        $data['slug'] = $this->uniqueSlug(AiProvider::class, $data['slug'] ?: $data['name'], $aiProvider->id);
        $data = $this->withBooleanValues($request, $data);

        $aiProvider->update($data);

        $this->enforceDefault($aiProvider, $aiProvider->is_default);

        return to_route('admin.ai-providers.index')->with('success', 'AI provider updated successfully.');
    }

    public function destroy(AiProvider $aiProvider): RedirectResponse
    {
        $aiProvider->delete();

        return to_route('admin.ai-providers.index')->with('success', 'AI provider deleted successfully.');
    }

    private function validated(Request $request, ?AiProvider $provider = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('ai_providers', 'slug')->ignore($provider?->id)],
            'provider_type' => ['required', 'string', 'max:50'],
            'base_url' => ['nullable', 'url'],
            'api_key_env' => ['nullable', 'string', 'max:255'],
            'default_model' => ['nullable', 'string', 'max:255'],
            'supports_web_search' => ['boolean'],
            'supports_json_mode' => ['boolean'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
            'monthly_budget_cents' => ['nullable', 'integer', 'min:0'],
            'cost_per_1k_input_tokens_cents' => ['nullable', 'integer', 'min:0'],
            'cost_per_1k_output_tokens_cents' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ]);
    }

    private function withBooleanValues(Request $request, array $data): array
    {
        $data['supports_web_search'] = $request->boolean('supports_web_search', false);
        $data['supports_json_mode'] = $request->boolean('supports_json_mode', false);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_default'] = $request->boolean('is_default', false);

        return $data;
    }

    private function enforceDefault(AiProvider $provider, bool $isDefault): void
    {
        if (! $isDefault) {
            return;
        }

        AiProvider::query()->where('id', '!=', $provider->id)->update(['is_default' => false]);
    }

    /** @return string[] */
    private function providerTypes(): array
    {
        return ['openai', 'openrouter', 'anthropic', 'google', 'local', 'mock', 'custom'];
    }
}
