<?php

namespace App\Services\Messages;

use App\Models\InternalMessage;
use App\Models\Script;
use App\Models\User;

class MessageRelatedLinkResolver
{
    public function resolveForUser(InternalMessage $message, User $user): ?array
    {
        if ($message->related_type === Script::class && $message->related_id) {
            $script = Script::query()->find($message->related_id);
            if (! $script) {
                return null;
            }

            if ($user->can('editor.access')) {
                return ['label' => 'Related script', 'url' => route('editor.scripts.show', $script), 'panel' => 'editor'];
            }

            if ($user->can('admin.access')) {
                return ['label' => 'Related script', 'url' => route('admin.scripts.show', $script), 'panel' => 'admin'];
            }
        }

        return null;
    }
}
