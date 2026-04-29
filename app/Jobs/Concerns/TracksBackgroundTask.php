<?php

namespace App\Jobs\Concerns;

use App\Models\BackgroundTask;
use App\Services\BackgroundTasks\BackgroundTaskService;
use Throwable;

trait TracksBackgroundTask
{
    protected ?int $backgroundTaskId = null;

    protected function task(): ?BackgroundTask
    {
        return $this->backgroundTaskId ? BackgroundTask::query()->find($this->backgroundTaskId) : null;
    }

    protected function markTaskRunning(?string $message = null): void
    {
        if ($task = $this->task()) app(BackgroundTaskService::class)->markRunning($task, $message);
    }
    protected function updateTaskProgress(int $progress, ?string $message = null): void
    {
        if ($task = $this->task()) app(BackgroundTaskService::class)->updateProgress($task, $progress, $message);
    }
    protected function markTaskCompleted(?string $message = null, array $metadata = []): void
    {
        if ($task = $this->task()) app(BackgroundTaskService::class)->markCompleted($task, $message, $metadata);
    }
    protected function markTaskFailed(Throwable|string $e, array $metadata = []): void
    {
        if ($task = $this->task()) app(BackgroundTaskService::class)->markFailed($task, $e, $metadata);
    }
}
