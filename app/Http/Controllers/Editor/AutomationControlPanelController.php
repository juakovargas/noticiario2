<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\BulletinType;
use App\Models\EditorialSchedule;
use App\Services\Automation\AutomationStatusService;
use App\Services\Scheduling\BulletinTypeScheduleSyncService;
use App\Services\Scheduling\EditorialScheduleRunner;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AutomationControlPanelController extends Controller
{
    public function __construct(
        private readonly AutomationStatusService $statusService,
        private readonly EditorialScheduleRunner $runner,
        private readonly BulletinTypeScheduleSyncService $scheduleSyncService,
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
        $run = $this->runner->createRunForSchedule($editorialSchedule->load('bulletinType'), now()->utc()->startOfMinute(), ['generate_prompts' => true]);
        return to_route('editor.editorial-schedule-runs.show', $run)->with('success', __('Manual run created.'));
    }

    public function recalculateNextRun(EditorialSchedule $editorialSchedule): RedirectResponse
    {
        $editorialSchedule->next_run_at = $this->runner->calculateNextRunAt($editorialSchedule);
        $editorialSchedule->save();
        return back()->with('success', __('Next run recalculated.'));
    }
}
