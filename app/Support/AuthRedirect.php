<?php

namespace App\Support;

use App\Models\User;

class AuthRedirect
{
    public static function routeNameFor(User $user): string
    {
        if ($user->hasRole('super-admin')) {
            return 'admin.dashboard';
        }

        if ($user->hasRole('admin')) {
            return 'admin.dashboard';
        }

        if ($user->hasRole('editor')) {
            return 'editor.dashboard';
        }

        if ($user->hasRole('viewer')) {
            return 'viewer.dashboard';
        }

        return 'dashboard';
    }
}
