<?php

namespace App\Services\BackgroundTasks;

use App\Models\BackgroundTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

class BackgroundTaskService
{
    public function create(string $taskType, ?Model $related = null, ?User $user = null, array $metadata = []): BackgroundTask
    {
        return BackgroundTask::query()->create([
            'uuid' => (string) Str::uuid(),
            'task_type' => $taskType,
            'status' => 'pending',
            'related_type' => $related?->getMorphClass(),
            'related_id' => $related?->getKey(),
            'user_id' => $user?->id,
            'metadata' => $metadata,
        ]);
    }
    public function markRunning(BackgroundTask $task, ?string $message = null): void { $task->update(['status'=>'running','started_at'=>now(),'message'=>$message,'attempts'=>$task->attempts+1]); }
    public function updateProgress(BackgroundTask $task, ?int $progress, ?string $message = null): void { $task->update(['progress'=>$progress,'message'=>$message ?? $task->message]); }
    public function markCompleted(BackgroundTask $task, ?string $message = null, array $metadata = []): void { $task->update(['status'=>'completed','finished_at'=>now(),'progress'=>100,'message'=>$message ?? $task->message,'metadata'=>array_merge($task->metadata ?? [], $metadata)]); }
    public function markFailed(BackgroundTask $task, Throwable|string $error, array $metadata = []): void { $msg = Str::limit(str_replace(['OPENAI_API_KEY','ANTHROPIC_API_KEY','OPENROUTER_API_KEY'],'[redacted]', (string) ($error instanceof Throwable ? $error->getMessage() : $error)), 4000); $task->update(['status'=>'failed','failed_at'=>now(),'error_message'=>$msg,'message'=>'Task failed','metadata'=>array_merge($task->metadata ?? [], $metadata)]); }
}
