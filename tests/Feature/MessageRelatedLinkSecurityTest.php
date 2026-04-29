<?php

namespace Tests\Feature;

use App\Models\InternalMessage;
use App\Models\Script;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MessageRelatedLinkSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_message_related_script_resolves_to_admin_route(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access', 'dashboard.view']);
        $script = Script::factory()->create();
        $message = InternalMessage::query()->create(['recipient_id' => $admin->id, 'subject' => 's', 'body' => 'b', 'message_type' => 'general', 'related_type' => Script::class, 'related_id' => $script->id, 'metadata' => ['related_url' => '/editor/scripts/'.$script->id]]);

        $this->actingAs($admin)->get(route('messages.show', $message))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('relatedLink.route_name', 'admin.scripts.show')
                ->where('relatedLink.url', route('admin.scripts.show', $script))
            );
    }

    public function test_editor_message_related_script_resolves_to_editor_route(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $script = Script::factory()->create();
        $message = InternalMessage::query()->create(['recipient_id' => $editor->id, 'subject' => 's', 'body' => 'b', 'message_type' => 'general', 'related_type' => Script::class, 'related_id' => $script->id]);

        $this->actingAs($editor)->get(route('messages.show', $message))->assertInertia(fn (Assert $page) => $page
            ->where('relatedLink.route_name', 'editor.scripts.show'));
    }

    public function test_viewer_message_related_script_has_no_accessible_link(): void
    {
        $viewer = $this->createUserWithPermissions(['viewer.access', 'viewer.dashboard.view']);
        $script = Script::factory()->create();
        $message = InternalMessage::query()->create(['recipient_id' => $viewer->id, 'subject' => 's', 'body' => 'b', 'message_type' => 'general', 'related_type' => Script::class, 'related_id' => $script->id]);

        $this->actingAs($viewer)->get(route('messages.show', $message))->assertInertia(fn (Assert $page) => $page
            ->where('relatedLink', null));
    }

    public function test_admin_can_open_admin_script_viewer_but_not_editor_script_page(): void
    {
        $admin = $this->createUserWithPermissions(['admin.access', 'dashboard.view']);
        $script = Script::factory()->create();

        $this->actingAs($admin)->get(route('admin.scripts.show', $script))->assertOk();
        $this->actingAs($admin)->get(route('editor.scripts.show', $script))->assertForbidden();
    }

    public function test_editor_cannot_open_admin_script_viewer(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);
        $script = Script::factory()->create();

        $this->actingAs($editor)->get(route('admin.scripts.show', $script))->assertForbidden();
    }
}
