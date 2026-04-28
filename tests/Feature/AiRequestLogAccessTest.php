<?php

namespace Tests\Feature;

use App\Models\AiRequestLog;
use App\Models\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class AiRequestLogAccessTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    public function test_admin_can_access_logs_and_filter_by_status(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access']);
        $provider = AiProvider::factory()->create();

        AiRequestLog::query()->create(['ai_provider_id' => $provider->id, 'status' => 'success']);
        AiRequestLog::query()->create(['ai_provider_id' => $provider->id, 'status' => 'failed', 'limit_blocked' => true, 'error_message' => 'Error']);

        $this->actingAs($admin)->get(route('admin.ai-request-logs.index', ['status' => 'failed', 'limit_blocked' => 1, 'has_error' => 1]))->assertOk()->assertSee('failed');
    }

    public function test_editor_cannot_access_admin_ai_logs(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);

        $this->actingAs($editor)->get(route('admin.ai-request-logs.index'))->assertForbidden();
    }
}
