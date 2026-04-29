<?php

namespace App\Services\Messages;

use App\Models\AiRequestLog;
use App\Models\BackgroundTask;
use App\Models\BulletinPromptRun;
use App\Models\InternalMessage;
use App\Models\Script;
use App\Models\SourceReference;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class MessageRelatedLinkResolver
{
    public function resolveForUser(InternalMessage $message, User $user): ?array
    {
        $panel = $this->resolvePanel($user);

        if (! $panel) {
            return null;
        }

        return match ($message->related_type) {
            Script::class => $this->resolveScript($message->related_id, $panel),
            SourceReference::class => $this->resolveSourceReference($message->related_id, $panel),
            AiRequestLog::class => $this->resolveAiRequestLog($message->related_id, $panel, $user),
            BackgroundTask::class => $this->resolveBackgroundTask($message->related_id, $panel, $user),
            BulletinPromptRun::class => $this->resolveBulletinPromptRun($message->related_id, $panel),
            default => null,
        };
    }

    private function resolvePanel(User $user): ?string
    {
        if ($user->can('admin.access')) {
            return 'admin';
        }

        if ($user->can('editor.access')) {
            return 'editor';
        }

        if ($user->can('viewer.access')) {
            return 'viewer';
        }

        return null;
    }

    private function resolveScript(?int $scriptId, string $panel): ?array
    {
        $script = $scriptId ? Script::query()->find($scriptId) : null;
        if (! $script) {
            return null;
        }

        return match ($panel) {
            'admin' => $this->safeRoute('admin.scripts.show', $script, 'Related script', 'admin'),
            'editor' => $this->safeRoute('editor.scripts.show', $script, 'Related script', 'editor'),
            default => null,
        };
    }

    private function resolveSourceReference(?int $id, string $panel): ?array
    {
        $model = $id ? SourceReference::query()->find($id) : null;
        if (! $model) {
            return null;
        }

        return match ($panel) {
            'admin' => $this->safeRoute('admin.source-references.show', $model, 'Related source reference', 'admin'),
            'editor' => $this->safeRoute('editor.source-references.show', $model, 'Related source reference', 'editor'),
            default => null,
        };
    }

    private function resolveAiRequestLog(?int $id, string $panel, User $user): ?array
    {
        $model = $id ? AiRequestLog::query()->find($id) : null;
        if (! $model) {
            return null;
        }

        if ($panel === 'admin') {
            return $this->safeRoute('admin.ai-request-logs.show', $model, 'Related AI request log', 'admin');
        }

        if ($panel === 'editor' && Route::has('editor.ai-request-logs.show') && ($model->user_id === $user->id || $model->bulletinPromptRun?->created_by === $user->id)) {
            return $this->safeRoute('editor.ai-request-logs.show', $model, 'Related AI request log', 'editor');
        }

        return null;
    }

    private function resolveBackgroundTask(?int $id, string $panel, User $user): ?array
    {
        $model = $id ? BackgroundTask::query()->find($id) : null;
        if (! $model) {
            return null;
        }

        if ($panel === 'admin') {
            return $this->safeRoute('admin.background-tasks.show', $model, 'Related background task', 'admin');
        }

        if ($panel === 'editor' && Route::has('editor.background-tasks.show') && $model->user_id === $user->id) {
            return $this->safeRoute('editor.background-tasks.show', $model, 'Related background task', 'editor');
        }

        return null;
    }

    private function resolveBulletinPromptRun(?int $id, string $panel): ?array
    {
        $model = $id ? BulletinPromptRun::query()->find($id) : null;
        if (! $model) {
            return null;
        }

        return match ($panel) {
            'admin' => $this->safeRoute('admin.bulletin-prompt-runs.show', $model, 'Related bulletin prompt run', 'admin'),
            'editor' => $this->safeRoute('editor.bulletin-prompt-runs.show', $model, 'Related bulletin prompt run', 'editor'),
            default => null,
        };
    }

    private function safeRoute(string $routeName, mixed $parameter, string $label, string $panel): ?array
    {
        if (! Route::has($routeName)) {
            return null;
        }

        return [
            'label' => $label,
            'url' => route($routeName, $parameter),
            'panel' => $panel,
            'route_name' => $routeName,
        ];
    }
}
