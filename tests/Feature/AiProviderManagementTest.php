<?php

namespace Tests\Feature;

use App\Models\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class AiProviderManagementTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    public function test_admin_can_create_and_update_ai_provider(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access']);

        $this->actingAs($admin)->post(route('admin.ai-providers.store'), [
            'name' => 'OpenRouter Provider',
            'slug' => '',
            'provider_type' => 'openrouter',
            'base_url' => 'https://openrouter.ai/api/v1',
            'api_key_env_name' => 'OPENROUTER_API_KEY',
            'default_model' => 'openai/gpt-4o-mini',
            'timeout_seconds' => 60,
            'is_active' => true,
            'is_default' => true,
        ])->assertRedirect(route('admin.ai-providers.index'));

        $provider = AiProvider::query()->firstOrFail();

        $this->actingAs($admin)->put(route('admin.ai-providers.update', $provider), [
            'name' => 'OpenRouter Updated',
            'slug' => '',
            'provider_type' => 'openrouter',
            'base_url' => 'https://openrouter.ai/api/v1',
            'api_key_env_name' => 'OPENROUTER_API_KEY',
            'default_model' => 'openai/gpt-4o-mini',
            'timeout_seconds' => 70,
            'is_active' => true,
        ])->assertRedirect(route('admin.ai-providers.index'));

        $this->assertDatabaseHas('ai_providers', ['id' => $provider->id, 'name' => 'OpenRouter Updated']);
    }

    public function test_editor_cannot_manage_ai_providers(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);

        $this->actingAs($editor)->get(route('admin.ai-providers.index'))->assertForbidden();
    }

    public function test_only_one_default_provider_exists(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access']);

        foreach (['A', 'B'] as $name) {
            $this->actingAs($admin)->post(route('admin.ai-providers.store'), [
                'name' => "Provider {$name}",
                'provider_type' => 'openai',
                'base_url' => 'https://api.openai.com/v1',
                'api_key_env_name' => 'OPENAI_API_KEY',
                'timeout_seconds' => 60,
                'is_default' => true,
            ])->assertRedirect();
        }

        $this->assertSame(1, AiProvider::query()->where('is_default', true)->count());
    }

    public function test_provider_env_key_status_does_not_expose_secret_value(): void
    {
        putenv('OPENAI_API_KEY=secret-value');

        $admin = $this->createUserWithPermissions(['admin.access']);
        AiProvider::query()->create([
            'name' => 'OpenAI',
            'slug' => 'openai',
            'provider_type' => 'openai',
            'base_url' => 'https://api.openai.com/v1',
            'api_key_env_name' => 'OPENAI_API_KEY',
            'timeout_seconds' => 60,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.ai-providers.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('providers.data.0.env_key_configured', true)
                ->missing('providers.data.0.api_key'));

        $this->actingAs($admin)->get(route('admin.ai-providers.index'))->assertDontSee('secret-value');
    }

    public function test_admin_provider_show_includes_usage_summary_payload(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access']);
        $provider = AiProvider::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.ai-providers.show', $provider))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('provider.usage_summary.daily_requests', 0)
                ->where('provider.usage_summary.monthly_requests', 0));
    }
}
