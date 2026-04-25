<?php

namespace App\Support;

use App\Models\User;

class AuthRedirect
{
    public static function routeNameFor(User $user): string
    {
        if ($user->can('admin.access')) {
            return 'admin.dashboard';
        }

        if ($user->can('editor.access') || $user->hasRole('editor')) {
            return 'editor.dashboard';
        }

        if ($user->can('viewer.access') || $user->hasRole('viewer')) {
            return 'viewer.dashboard';
        }

        return 'dashboard';
    }
}
