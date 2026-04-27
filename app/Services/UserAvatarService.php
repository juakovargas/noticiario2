<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserAvatarService
{
    public function storeAvatar(User $user, UploadedFile $file): string
    {
        $extension = $file->guessExtension() ?: $file->extension() ?: 'jpg';
        $filename = now()->format('YmdHis').'-'.Str::uuid().'.'.$extension;

        return $file->storeAs("avatars/users/{$user->id}", $filename, 'public');
    }

    public function deleteAvatar(User $user): void
    {
        if (! $user->avatar_path) {
            return;
        }

        if (! str_starts_with($user->avatar_path, "avatars/users/{$user->id}/")) {
            return;
        }

        if (Storage::disk('public')->exists($user->avatar_path)) {
            Storage::disk('public')->delete($user->avatar_path);
        }
    }

    public function replaceAvatar(User $user, UploadedFile $file): string
    {
        $this->deleteAvatar($user);

        return $this->storeAvatar($user, $file);
    }
}
