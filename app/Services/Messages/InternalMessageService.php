<?php

namespace App\Services\Messages;

use App\Models\InternalMessage;
use App\Models\Script;
use App\Models\User;

class InternalMessageService
{
    public function send(?User $sender, User $recipient, string $subject, string $body, array $options = []): InternalMessage
    {
        return InternalMessage::query()->create([
            'sender_id' => $sender?->id,
            'recipient_id' => $recipient->id,
            'message_type' => $options['message_type'] ?? 'general',
            'subject' => $subject,
            'body' => $body,
            'related_type' => $options['related_type'] ?? null,
            'related_id' => $options['related_id'] ?? null,
            'metadata' => $options['metadata'] ?? [],
        ]);
    }

    public function sendScriptRejected(Script $script, User $reviewer, ?string $reason = null): ?InternalMessage
    {
        $creator = $script->bulletinPromptRun?->createdBy;
        $recipient = $creator?->manager ?? (($creator && $creator->id !== $reviewer->id) ? $creator : null);

        if (! $recipient) {
            $recipient = User::role('superadmin')->first() ?? User::role('admin')->first() ?? User::query()->first();
        }

        if (! $recipient) {
            return null;
        }

        $exists = InternalMessage::query()
            ->where('recipient_id', $recipient->id)
            ->where('message_type', 'script_rejected')
            ->where('related_type', Script::class)
            ->where('related_id', $script->id)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->exists();

        if ($exists) {
            return null;
        }

        return $this->send(
            $reviewer,
            $recipient,
            'Script rejected: '.($script->final_title ?: $script->title),
            "Reviewer: {$reviewer->name}\nStatus: {$script->review_status}\nReason: ".($reason ?: '-')."\nScript ID: {$script->id}",
            [
                'message_type' => 'script_rejected',
                'related_type' => Script::class,
                'related_id' => $script->id,
                'metadata' => [
                    'script_id' => $script->id,
                    'related_label' => 'Related script',
                ],
            ],
        );
    }

    public function markRead(InternalMessage $message, User $user): void
    {
        if ($message->recipient_id === $user->id && ! $message->read_at) {
            $message->update(['read_at' => now()]);
        }
    }

    public function unreadCount(User $user): int
    {
        return InternalMessage::query()->inboxFor($user)->unread()->count();
    }
}
