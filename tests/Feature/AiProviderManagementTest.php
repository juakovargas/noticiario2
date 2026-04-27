<?php

namespace Tests\Feature;

use App\Models\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class AiProviderManagementTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    public function test_admin_can_manage_ai_providers(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access']);

        $this->actingAs($admin)->post(route('admin.ai-providers.store'), [
            'name' => 'Mock AI Provider',
            'slug' => '',
            'provider_type' => 'mock',
            'default_model' => 'mock-editorial-v1',
            'supports_web_search' => true,
            'supports_json_mode' => true,
            'is_active' => true,
            'is_default' => true,
        ])->assertRedirect(route('admin.ai-providers.index'));

        $provider = AiProvider::query()->firstOrFail();
        $this->assertSame('mock-ai-provider', $provider->slug);

        $this->actingAs($admin)->put(route('admin.ai-providers.update', $provider), [
            'name' => 'Mock Provider Updated',
            'slug' => '',
            'provider_type' => 'mock',
            'is_active' => true,
        ])->assertRedirect(route('admin.ai-providers.index'));

        $this->assertDatabaseHas('ai_providers', ['id' => $provider->id, 'name' => 'Mock Provider Updated']);

        $this->actingAs($admin)->delete(route('admin.ai-providers.destroy', $provider))->assertRedirect(route('admin.ai-providers.index'));
        $this->assertSoftDeleted('ai_providers', ['id' => $provider->id]);
    }

    public function test_editor_cannot_manage_admin_ai_providers(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);

        $this->actingAs($editor)->get(route('admin.ai-providers.index'))->assertForbidden();
    }

    public function test_default_provider_uniqueness_is_enforced(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access']);

        $this->actingAs($admin)->post(route('admin.ai-providers.store'), [
            'name' => 'Provider A',
            'provider_type' => 'mock',
            'is_default' => true,
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.ai-providers.store'), [
            'name' => 'Provider B',
            'provider_type' => 'mock',
            'is_default' => true,
        ])->assertRedirect();

        $this->assertSame(1, AiProvider::query()->where('is_default', true)->count());
        $this->assertDatabaseHas('ai_providers', ['name' => 'Provider B', 'is_default' => true]);
        $this->assertDatabaseHas('ai_providers', ['name' => 'Provider A', 'is_default' => false]);
    }
}
