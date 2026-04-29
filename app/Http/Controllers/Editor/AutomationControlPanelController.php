<?php
namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\BulletinType;
use App\Models\EditorialSchedule;
use App\Services\Automation\AutomationStatusService;
use App\Services\Scheduling\EditorialScheduleRunner;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AutomationControlPanelController extends Controller
{
    public function __construct(private readonly AutomationStatusService $statusService, private readonly EditorialScheduleRunner $runner) {}

    public function index(): Response
    {
        return Inertia::render('Editor/Automation/Index', [
            'bulletins' => $this->statusService->getBulletinAutomationOverview(),
        ]);
    }

    public function toggleSchedule(EditorialSchedule $editorialSchedule): RedirectResponse
    {
        $next = ! $editorialSchedule->is_active;
        $editorialSchedule->is_active = $next;
        if ($next && ! $editorialSchedule->next_run_at) {
            $editorialSchedule->next_run_at = $this->runner->calculateNextRunAt($editorialSchedule);
        }
        $editorialSchedule->save();
        return back()->with('success', 'Schedule updated.');
    }

    public function toggleBulletinType(BulletinType $bulletinType): RedirectResponse
    {
        $schedules = $bulletinType->schedules;
        if ($schedules->isEmpty()) return back()->with('error', 'This bulletin has no schedules configured.');
        $hasActive = $schedules->contains(fn($s)=>(bool)$s->is_active);
        foreach ($schedules as $schedule) {
            $schedule->is_active = ! $hasActive;
            if (! $hasActive && ! $schedule->next_run_at) $schedule->next_run_at = $this->runner->calculateNextRunAt($schedule);
            $schedule->save();
        }
        return back()->with('success', 'Schedules updated.');
    }

    public function runNow(EditorialSchedule $editorialSchedule): RedirectResponse
    {
        $run = $this->runner->createRunForSchedule($editorialSchedule->load('bulletinType'), now()->utc()->startOfMinute(), ['generate_prompts' => true]);
        return to_route('editor.editorial-schedule-runs.show', $run)->with('success', 'Manual run created.');
    }
}
