<?php

namespace Tests\Feature;

use App\Models\InternalMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InternalMessagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_messages_routes_exist(): void
    {
        $this->assertTrue(route('messages.index', absolute: false) === '/messages');
        $this->assertStringContainsString('/messages/', route('messages.show', ['internalMessage' => 1], false));
        $this->assertStringContainsString('/mark-read', route('messages.mark-read', ['internalMessage' => 1], false));
        $this->assertStringContainsString('/archive', route('messages.archive', ['internalMessage' => 1], false));
    }

    public function test_guest_cannot_view_messages(): void
    {
        $this->get(route('messages.index'))->assertRedirect(route('login'));
    }

    public function test_user_can_only_view_own_messages(): void
    {
        $owner = $this->createUserWithPermissions(['viewer.access', 'viewer.dashboard.view']);
        $other = $this->createUserWithPermissions(['viewer.access', 'viewer.dashboard.view']);

        $message = InternalMessage::query()->create([
            'recipient_id' => $owner->id,
            'subject' => 'Test subject',
            'body' => 'Body',
            'message_type' => 'general',
        ]);

        $this->actingAs($owner)->get(route('messages.index'))->assertOk();
        $this->actingAs($owner)->get(route('messages.show', $message))->assertOk();
        $this->actingAs($other)->get(route('messages.show', $message))->assertForbidden();
    }

    public function test_shared_props_include_unread_count(): void
    {
        $user = $this->createUserWithPermissions(['editor.access', 'editor.dashboard.view']);

        InternalMessage::query()->create(['recipient_id' => $user->id, 'subject' => 'U', 'body' => 'B', 'message_type' => 'general']);
        InternalMessage::query()->create(['recipient_id' => $user->id, 'subject' => 'R', 'body' => 'B', 'message_type' => 'general', 'read_at' => now()]);
        InternalMessage::query()->create(['recipient_id' => $user->id, 'subject' => 'A', 'body' => 'B', 'message_type' => 'general', 'archived_at' => now()]);

        $this->actingAs($user)
            ->get(route('editor.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('messages.unread_count', 1)
            );
    }
}
