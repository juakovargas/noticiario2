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
        return Inertia::render('Editor/EditorialDesk/Index', [
            'todayRuns' => EditorialScheduleRun::query()
                ->with(['schedule:id,name', 'edition:id,title'])
                ->whereDate('scheduled_for', now()->toDateString())
                ->latest()
                ->limit(20)
                ->get(),
            'upcomingSchedules' => EditorialSchedule::query()
                ->with(['location:id,name', 'language:id,code'])
                ->where('is_active', true)
                ->orderBy('name')
                ->limit(20)
                ->get(),
            'statusCounts' => [
                'needs_prompt' => EditorialScheduleRun::query()->whereIn('status', ['pending'])->count(),
                'waiting_ai_response' => EditorialScheduleRun::query()->whereIn('status', ['prompt_ready', 'waiting_ai_response'])->count(),
                'response_received' => EditorialScheduleRun::query()->where('status', 'response_received')->count(),
                'script_created' => EditorialScheduleRun::query()->where('status', 'script_created')->count(),
            ],
        ]);
    }
}
