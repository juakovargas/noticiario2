<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\BulletinType;
use App\Models\EditorialSchedule;
use App\Services\Automation\AutomationStatusService;
use App\Services\Scheduling\BulletinTypeScheduleSyncService;
use App\Services\Scheduling\EditorialScheduleRunner;
use App\Services\Pipelines\BulletinPromptRunPipeline;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AutomationControlPanelController extends Controller
{
    public function __construct(
        private readonly AutomationStatusService $statusService,
        private readonly EditorialScheduleRunner $runner,
        private readonly BulletinTypeScheduleSyncService $scheduleSyncService,
        private readonly BulletinPromptRunPipeline $pipelineService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Editor/Automation/Index', ['bulletins' => $this->statusService->getBulletinAutomationOverview()->values()]);
    }

    public function syncBulletinTypeSchedule(BulletinType $bulletinType): RedirectResponse
    {
        $this->scheduleSyncService->syncPrimarySchedule($bulletinType);
        return back()->with('success', __('Schedule synchronized.'));
    }

    public function syncSchedules(): RedirectResponse
    {
        BulletinType::query()->get()->each(fn (BulletinType $bt) => $this->scheduleSyncService->syncPrimarySchedule($bt));
        return back()->with('success', __('Schedules synchronized.'));
    }

    public function toggleSchedule(EditorialSchedule $editorialSchedule): RedirectResponse
    {
        $editorialSchedule->is_active = ! $editorialSchedule->is_active;
        if ($editorialSchedule->is_active && ! $editorialSchedule->next_run_at) {
            $editorialSchedule->next_run_at = $this->runner->calculateNextRunAt($editorialSchedule);
        }
        $editorialSchedule->save();
        return back()->with('success', __('Schedule updated.'));
    }

    public function runNow(EditorialSchedule $editorialSchedule): RedirectResponse
    {
        $scheduledFor = now()->utc()->startOfMinute();
        $run = $this->runner->createRunForSchedule($editorialSchedule->load('bulletinType'), $scheduledFor, ['generate_prompts' => true]);

        if (! $run->wasRecentlyCreated) {
            return back()->with('warning', __('Execution already exists for this scheduled time.'));
        }

        return to_route('editor.editorial-schedule-runs.show', $run)->with('success', __('Manual run created.'));
    }

    public function runOverdueNow(EditorialSchedule $editorialSchedule): RedirectResponse
    {
        if (! $editorialSchedule->next_run_at || ! $editorialSchedule->is_active) {
            return back()->with('warning', __('Schedule is not overdue.'));
        }

        $scheduledFor = $editorialSchedule->next_run_at->copy()->utc()->startOfMinute();
        if ($scheduledFor->isFuture()) {
            return back()->with('warning', __('Schedule is not overdue.'));
        }

        $run = $this->runner->createRunForSchedule($editorialSchedule->load('bulletinType'), $scheduledFor, ['generate_prompts' => true]);

        if (! $run->wasRecentlyCreated) {
            $editorialSchedule->next_run_at = $this->runner->calculateNextRunAt($editorialSchedule, $scheduledFor->copy()->addMinute());
            $editorialSchedule->save();
            return back()->with('warning', __('Execution already exists for this scheduled time. Next run recalculated.'));
        }

        return to_route('editor.editorial-schedule-runs.show', $run)->with('success', __('Missed execution processed and next run scheduled.'));
    }


    public function runNowAndScheduleNext(EditorialSchedule $editorialSchedule): RedirectResponse
    {
        $editorialSchedule->load('bulletinType');
        $scheduledFor = $editorialSchedule->next_run_at && $editorialSchedule->next_run_at->lessThanOrEqualTo(now())
            ? $editorialSchedule->next_run_at->copy()->utc()->startOfMinute()
            : now()->utc()->startOfMinute();

        $run = $this->runner->createRunForSchedule($editorialSchedule, $scheduledFor, ['generate_prompts' => false]);
        $promptRun = $run->bulletinPromptRun;

        if (! $promptRun) {
            return back()->with('error', __('Pipeline failed').': '.(__('The process stopped at this step')).': prompt_generation');
        }

        $summary = $this->pipelineService->run($promptRun->refresh(), request()->user(), [
            'allow_ai_call' => true,
            'generate_metadata' => true,
            'extract_sources' => true,
        ]);

        $editorialSchedule->refresh();
        if ($summary['success']) {
            $editorialSchedule->forceFill(['last_success_at' => now()])->save();
            if ($summary['script_id']) {
                return to_route('editor.scripts.show', $summary['script_id'])->with('success', __('Pipeline completed. Script created.'));
            }
            return to_route('editor.bulletin-prompt-runs.show', $promptRun)->with('success', __('Pipeline completed'));
        }

        $editorialSchedule->forceFill(['last_failure_at' => now()])->save();
        return to_route('editor.bulletin-prompt-runs.show', $promptRun)
            ->with('error', __('Pipeline failed').': '.($summary['failed_step'] ?? 'unknown').' - '.($summary['message'] ?? '')) ;
    }

    public function createPromptOnly(EditorialSchedule $editorialSchedule): RedirectResponse
    {
        $scheduledFor = now()->utc()->startOfMinute();
        $run = $this->runner->createRunForSchedule($editorialSchedule->load('bulletinType'), $scheduledFor, ['generate_prompts' => true]);
        return to_route('editor.editorial-schedule-runs.show', $run)->with('success', __('Prompt generated successfully.'));
    }

    public function recalculateNextRun(EditorialSchedule $editorialSchedule): RedirectResponse
    {
        $editorialSchedule->next_run_at = $this->runner->calculateNextRunAt($editorialSchedule);
        $editorialSchedule->save();
        return back()->with('success', __('Next run recalculated.'));
    }
}
