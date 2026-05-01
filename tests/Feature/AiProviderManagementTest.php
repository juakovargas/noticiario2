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

    public function test_index_shows_admin_actions_and_driver_column(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access']);
        AiProvider::factory()->create(['name' => 'Gemini Flash', 'client_driver' => 'custom', 'is_active' => true]);

        $this->actingAs($admin)->get(route('admin.ai-providers.index'))
            ->assertOk()
            ->assertSee('View')
            ->assertSee('Edit')
            ->assertSee('Driver')
            ->assertSee('Diagnostics');
    }

    public function test_admin_can_toggle_active_and_update_provider_and_make_default(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access']);
        $a = AiProvider::factory()->create(['is_active' => true, 'is_default' => true, 'provider_type' => 'groq']);
        $b = AiProvider::factory()->create(['is_active' => true, 'is_default' => false, 'provider_type' => 'gemini']);

        $this->actingAs($admin)->post(route('admin.ai-providers.toggle-active', $b))->assertRedirect();
        $this->assertDatabaseHas('ai_providers', ['id' => $b->id, 'is_active' => false]);

        $this->actingAs($admin)->put(route('admin.ai-providers.update', $a), [
            'name' => 'Updated', 'provider_type' => $a->provider_type, 'provider_category' => 'text', 'timeout_seconds' => 70,
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.ai-providers.make-default', $a))->assertRedirect();
        $this->assertDatabaseHas('ai_providers', ['id' => $a->id, 'is_default' => true]);
        $this->assertDatabaseMissing('ai_providers', ['id' => $b->id, 'is_default' => true]);
    }

    public function test_show_includes_usage_summary_payload(): void
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
