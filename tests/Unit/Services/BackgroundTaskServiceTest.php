<?php

namespace Tests\Unit\Services;

use App\Models\BackgroundTask;
use App\Services\BackgroundTasks\BackgroundTaskService;
use Tests\TestCase;

class BackgroundTaskServiceTest extends TestCase
{
    public function test_it_creates_and_updates_task_states(): void
    {
        $service = app(BackgroundTaskService::class);
        $task = $service->create('test_task');
        $this->assertSame('pending', $task->status);

        $service->markRunning($task, 'running');
        $this->assertSame('running', $task->fresh()->status);

        $service->updateProgress($task->fresh(), 40, 'progress');
        $this->assertSame(40, $task->fresh()->progress);

        $service->markCompleted($task->fresh(), 'done');
        $this->assertSame('completed', $task->fresh()->status);

        $task2 = $service->create('test_fail');
        $service->markFailed($task2, 'OPENAI_API_KEY leaked');
        $this->assertStringNotContainsString('OPENAI_API_KEY', (string) $task2->fresh()->error_message);
    }
}
