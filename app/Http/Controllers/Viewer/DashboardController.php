<?php

namespace App\Http\Controllers\Viewer;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\EditorialScheduleRun;
use App\Models\Script;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Viewer/Dashboard', [
            'recentCompletedScripts' => Script::query()
                ->select(['id', 'title', 'status', 'updated_at'])
                ->whereIn('status', ['approved', 'archived', 'review'])
                ->latest('updated_at')
                ->limit(8)
                ->get(),
            'recentCompletedRuns' => EditorialScheduleRun::query()
                ->with(['schedule:id,name', 'script:id,title'])
                ->whereIn('status', ['script_created', 'completed'])
                ->latest('completed_at')
                ->limit(8)
                ->get(),
            'upcomingEditions' => Edition::query()
                ->select(['id', 'title', 'scheduled_for', 'status'])
                ->whereNotNull('scheduled_for')
                ->whereDate('scheduled_for', '>=', now()->toDateString())
                ->orderBy('scheduled_for')
                ->limit(8)
                ->get(),
        ]);
    }
}
