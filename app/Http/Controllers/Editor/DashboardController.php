<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\EditorialSchedule;
use App\Models\EditorialScheduleRun;
use App\Models\Script;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $today = now()->toDateString();

        $todayRunsQuery = EditorialScheduleRun::query()
            ->with([
                'schedule:id,name,location_id,news_category_id,language_id',
                'schedule.location:id,name',
                'schedule.newsCategory:id,name',
                'schedule.language:id,name,code',
                'script:id,title,status',
            ])
            ->whereDate('scheduled_for', $today);

        $pendingPromptRuns = EditorialScheduleRun::query()
            ->with(['schedule:id,name'])
            ->where('status', 'pending')
            ->orderBy('scheduled_for')
            ->limit(10)
            ->get();

        $waitingResponseRuns = EditorialScheduleRun::query()
            ->with(['schedule:id,name'])
            ->whereIn('status', ['prompt_ready', 'waiting_ai_response'])
            ->orderBy('scheduled_for')
            ->limit(10)
            ->get();

        $responseReceivedRuns = EditorialScheduleRun::query()
            ->with(['schedule:id,name'])
            ->where('status', 'response_received')
            ->orderBy('scheduled_for')
            ->limit(10)
            ->get();

        $scriptsNeedingReview = Script::query()
            ->select(['id', 'title', 'status', 'edition_id', 'updated_at'])
            ->whereIn('status', ['draft', 'review'])
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        $upcomingSchedules = EditorialSchedule::query()
            ->with(['location:id,name', 'newsCategory:id,name', 'language:id,name,code'])
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(10)
            ->get();

        return Inertia::render('Editor/Dashboard', [
            'stats' => [
                'activeSchedules' => EditorialSchedule::query()->where('is_active', true)->count(),
                'todayRuns' => (clone $todayRunsQuery)->count(),
                'pendingPrompts' => EditorialScheduleRun::query()->where('status', 'pending')->count(),
                'waitingResponses' => EditorialScheduleRun::query()->whereIn('status', ['prompt_ready', 'waiting_ai_response'])->count(),
                'draftScripts' => Script::query()->whereIn('status', ['draft', 'review'])->count(),
                'plannedEditions' => Edition::query()->whereIn('status', ['planning', 'scripting'])->count(),
            ],
            'todayRuns' => $todayRunsQuery->orderBy('scheduled_for')->get(),
            'pendingPromptRuns' => $pendingPromptRuns,
            'waitingResponseRuns' => $waitingResponseRuns,
            'responseReceivedRuns' => $responseReceivedRuns,
            'scriptsNeedingReview' => $scriptsNeedingReview,
            'upcomingSchedules' => $upcomingSchedules,
        ]);
    }
}
