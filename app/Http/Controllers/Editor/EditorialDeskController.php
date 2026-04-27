<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\EditorialSchedule;
use App\Models\EditorialScheduleRun;
use Inertia\Inertia;
use Inertia\Response;

class EditorialDeskController extends Controller
{
    public function index(): Response
    {
        $today = now()->toDateString();

        $baseQuery = EditorialScheduleRun::query()->with([
            'schedule:id,name,location_id,news_category_id,language_id',
            'schedule.location:id,name',
            'schedule.newsCategory:id,name',
            'schedule.language:id,name,code',
            'edition:id,title,scheduled_for',
            'script:id,title,status',
        ]);

        return Inertia::render('Editor/EditorialDesk/Index', [
            'todayRuns' => (clone $baseQuery)->whereDate('scheduled_for', $today)->orderBy('scheduled_for')->get(),
            'promptReadyRuns' => (clone $baseQuery)->where('status', 'prompt_ready')->orderBy('scheduled_for')->limit(20)->get(),
            'waitingAiResponseRuns' => (clone $baseQuery)->whereIn('status', ['prompt_ready', 'waiting_ai_response'])->orderBy('scheduled_for')->limit(20)->get(),
            'responseReceivedRuns' => (clone $baseQuery)->where('status', 'response_received')->orderBy('scheduled_for')->limit(20)->get(),
            'scriptCreatedRuns' => (clone $baseQuery)->whereIn('status', ['script_created', 'completed'])->orderByDesc('scheduled_for')->limit(20)->get(),
            'upcomingSchedules' => EditorialSchedule::query()
                ->with(['location:id,name', 'newsCategory:id,name', 'language:id,name,code'])
                ->where('is_active', true)
                ->orderBy('name')
                ->limit(20)
                ->get(),
        ]);
    }
}
