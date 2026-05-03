<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiProvider;
use App\Services\Ai\AiProviderRateLimiter;
use App\Services\Ai\AiUsageLimitService;
use App\Services\Ai\AiClientManager;
use App\Services\Ai\AiRequestTraceBuilder;
use App\Services\Ai\AiProviderTestRunner;
use App\Services\Ai\Exceptions\AiProviderException;
use Throwable;
use App\Support\GeneratesUniqueSlug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AiProviderController extends Controller
{
    use GeneratesUniqueSlug;

    public function __construct(private readonly AiUsageLimitService $usageLimitService, private readonly AiProviderRateLimiter $rateLimiter, private readonly AiClientManager $aiClientManager, private readonly AiRequestTraceBuilder $traceBuilder, private readonly AiProviderTestRunner $testRunner)
    {
    }

    public function index(): Response
    {
        return Inertia::render('Admin/AiProviders/Index', [
            'providers' => AiProvider::query()->orderByDesc('is_default')->orderBy('name')->get()->map(
                fn (AiProvider $provider): array => $this->presentProvider($provider)
            ),
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

        return to_route('admin.ai-providers.index')->with('success', __('Provider saved successfully.'));
    }

    public function show(AiProvider $aiProvider): Response
    {
        return Inertia::render('Admin/AiProviders/Show', ['provider' => $this->presentProvider($aiProvider)]);
    }

    public function edit(AiProvider $aiProvider): Response
    {
        return Inertia::render('Admin/AiProviders/Edit', ['provider' => $aiProvider, 'providerTypes' => $this->providerTypes()]);
    }

    public function update(Request $request, AiProvider $aiProvider): RedirectResponse
    {
        try {
            $data = $this->validated($request, $aiProvider);
            $data['slug'] = $this->uniqueSlug(AiProvider::class, $data['slug'] ?: $data['name'], $aiProvider->id);
            $data = $this->withBooleanValues($request, $data);

            $aiProvider->update($data);

            $this->enforceDefault($aiProvider, $aiProvider->is_default);

            return to_route('admin.ai-providers.index')->with('success', __('Provider saved successfully.'));
        } catch (\Throwable $e) {
            Log::error('Failed to update AI provider', ['provider_id' => $aiProvider->id, 'error' => $e->getMessage()]);

            return back()->withInput()->withErrors(['provider' => __('Unable to update provider. Please review the highlighted fields.')]);
        }
    }

    public function toggleActive(AiProvider $aiProvider): RedirectResponse
    {
        if ($aiProvider->is_default && $aiProvider->is_active) {
            return back()->with('error', __('Cannot deactivate the current default provider'));
        }
        $aiProvider->update(['is_active' => ! $aiProvider->is_active]);
        return back()->with('success', $aiProvider->is_active ? __('Provider activated') : __('Provider deactivated'));
    }

    public function makeDefault(AiProvider $aiProvider): RedirectResponse
    {
        if (! $aiProvider->is_active) {
            return back()->with('error', __('Default provider must be active'));
        }
        AiProvider::query()->where('id', '!=', $aiProvider->id)->update(['is_default' => false]);
        $aiProvider->update(['is_default' => true]);
        return back()->with('success', __('Provider set as default'));
    }


    public function test(Request $request, AiProvider $aiProvider): JsonResponse
    {
        $type = (string) $request->input('test_type', 'minimal');
        $normalized = $type === 'grounded' ? 'grounded_search' : $type;
        $result = $this->testRunner->run($aiProvider, $normalized);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function latestTrace(AiProvider $aiProvider): Response
    {
        return Inertia::render('Admin/AiProviders/Show', [
            'provider' => $this->presentProvider($aiProvider),
            'latestTrace' => session('provider_test_result'),
        ]);
    }

    public function testMinimal(AiProvider $aiProvider): JsonResponse
    {
        return $this->test(request()->merge(['test_type' => 'minimal']), $aiProvider);
    }

    public function testShortScript(AiProvider $aiProvider): JsonResponse
    {
        return $this->test(request()->merge(['test_type' => 'short_script']), $aiProvider);
    }

    public function testGrounded(AiProvider $aiProvider): JsonResponse
    {
        return $this->test(request()->merge(['test_type' => 'grounded_search']), $aiProvider);
    }

    public function testOfficialMinimalGemini(AiProvider $aiProvider): JsonResponse
    {
        return $this->test(request()->merge(['test_type' => 'official_minimal_gemini']), $aiProvider);
    }

public function clearRateLimitLock(AiProvider $aiProvider): RedirectResponse
    {
        $this->rateLimiter->clearRateLimitLock($aiProvider);

        return back()->with('success', __('Rate limit lock cleared'));
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
            'provider_type' => ['required', Rule::in($this->providerTypes())],
            'provider_category' => ['required', 'string', 'max:50'],
            'execution_driver' => ['required', 'string', Rule::in([AiProvider::DRIVER_CUSTOM, AiProvider::DRIVER_LARAVEL_AI])],
            'base_url' => ['nullable', 'url'],
            'api_key_env_name' => ['nullable', 'string', 'max:255'],
            'default_model' => ['nullable', 'string', 'max:255'],
            'organization' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
            'timeout_seconds' => ['required', 'integer', 'min:5', 'max:300'],
            'max_tokens' => ['nullable', 'integer', 'min:1'],
            'temperature' => ['nullable', 'numeric', 'min:0', 'max:2'],
            'cost_input_per_1k_tokens' => ['nullable', 'numeric', 'min:0'],
            'cost_output_per_1k_tokens' => ['nullable', 'numeric', 'min:0'],
            'daily_request_limit' => ['nullable', 'integer', 'min:0'],
            'monthly_request_limit' => ['nullable', 'integer', 'min:0'],
            'daily_cost_limit' => ['nullable', 'numeric', 'min:0'],
            'monthly_cost_limit' => ['nullable', 'numeric', 'min:0'],

            'requests_per_minute_limit' => ['nullable', 'integer', 'min:0'],
            'requests_per_day_limit' => ['nullable', 'integer', 'min:0'],
            'tokens_per_minute_limit' => ['nullable', 'integer', 'min:0'],
            'min_seconds_between_requests' => ['nullable', 'integer', 'min:0'],
            'retry_on_rate_limit' => ['boolean'],
            'max_retries' => ['nullable', 'integer', 'min:0', 'max:20'],
            'initial_retry_delay_seconds' => ['nullable', 'integer', 'min:1', 'max:600'],
            'max_retry_delay_seconds' => ['nullable', 'integer', 'min:1', 'max:1800'],
            'backoff_multiplier' => ['nullable', 'numeric', 'min:1', 'max:10'],
            'jitter_enabled' => ['boolean'],
            'metadata' => ['nullable', 'array'],
            'capabilities' => ['nullable', 'array'],
            'is_testing' => ['boolean'],
            'is_local' => ['boolean'],
            'supports_grounding' => ['boolean'],
            'supports_citations' => ['boolean'],
            'supports_streaming' => ['boolean'],
        ]);
    }

    private function withBooleanValues(Request $request, array $data): array
    {
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_default'] = $request->boolean('is_default', false);
        $data['is_testing'] = $request->boolean('is_testing', false);
        $data['is_local'] = $request->boolean('is_local', false);
        $data['supports_grounding'] = $request->boolean('supports_grounding', false);
        $data['supports_citations'] = $request->boolean('supports_citations', false);
        $data['supports_streaming'] = $request->boolean('supports_streaming', false);
        $data['retry_on_rate_limit'] = $request->boolean('retry_on_rate_limit', true);
        $data['jitter_enabled'] = $request->boolean('jitter_enabled', true);
        $data['capabilities'] = array_values(array_filter($request->input('capabilities', [])));
        $driver = $data['execution_driver'] ?? null;
        $data['client_driver'] = in_array($driver, [AiProvider::DRIVER_CUSTOM, AiProvider::DRIVER_LARAVEL_AI], true) ? $driver : AiProvider::DRIVER_CUSTOM;

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
        return ['text', 'openai', 'openrouter', 'anthropic', 'ollama', 'custom_openai_compatible', 'groq', 'mock', 'gemini', 'google_gemini', 'gemini_grounded', 'edge_tts', 'elevenlabs', 'google_tts', 'remotion', 'ffmpeg'];
    }

    /** @return array<string,mixed> */
    private function presentProvider(AiProvider $provider): array
    {
        return [
            ...$provider->toArray(),
            'env_key_configured' => $provider->hasConfiguredApiKey(),
            'usage_summary' => $this->usageLimitService->checkProviderLimits($provider),
            'execution_driver' => $provider->executionDriver(),
            'execution_driver_label' => $provider->usesLaravelAiDriver() ? 'Laravel AI SDK' : 'Custom Noticiario',
            'last_request' => $provider->aiRequestLogs()->latest()->first(['id', 'status', 'created_at']),
        ];
    }
}
