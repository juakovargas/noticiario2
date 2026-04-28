<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\BulletinPromptRun;
use App\Models\BulletinType;
use App\Models\Edition;
use App\Models\EditorialSchedule;
use App\Models\EditorialScheduleRun;
use App\Models\Script;
use App\Models\SourceReference;
use Illuminate\Database\Eloquent\Builder;
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
            ->select(['id', 'title', 'status', 'review_status', 'edition_id', 'updated_at'])
            ->whereIn('review_status', ['pending', 'in_review', 'needs_sources', 'needs_changes'])
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        $readyToApprove = Script::query()
            ->select(['id', 'title', 'status', 'review_status', 'updated_at'])
            ->where('review_status', 'verified')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        $scriptsBlockedBySources = Script::query()
            ->whereHas('sourceReferences', fn (Builder $query) => $query->withoutArchived()->whereIn('verification_status', ['missing', 'broken', 'rejected']))
            ->select(['id', 'title', 'status', 'review_status'])
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
                'activeBulletinTypes' => BulletinType::query()->where('is_active', true)->count(),
                'recentPromptRuns' => BulletinPromptRun::query()->where('status', '!=', 'archived')->count(),
                'promptRunsWaitingAiResponse' => BulletinPromptRun::query()->where('status', '!=', 'archived')->whereIn('status', ['prompt_ready', 'waiting_ai_response'])->count(),
                'promptRunsReadyForAiGeneration' => BulletinPromptRun::query()->where('status', 'prompt_ready')->whereNull('ai_response_text')->count(),
                'promptRunsReadyToCreateScript' => BulletinPromptRun::query()->where('status', 'response_received')->count(),
                'todayRuns' => (clone $todayRunsQuery)->count(),
                'pendingPrompts' => EditorialScheduleRun::query()->where('status', 'pending')->count(),
                'waitingResponses' => EditorialScheduleRun::query()->whereIn('status', ['prompt_ready', 'waiting_ai_response'])->count(),
                'draftScripts' => Script::query()->where('status', '!=', 'archived')->whereIn('status', ['draft', 'review'])->count(),
                'plannedEditions' => Edition::query()->where('status', '!=', 'archived')->whereIn('status', ['planning', 'scripting'])->count(),
                'scriptsPendingReview' => Script::query()->where('status', '!=', 'archived')->where('review_status', 'pending')->count(),
                'scriptsNeedingSources' => Script::query()->where('status', '!=', 'archived')->where('review_status', 'needs_sources')->count(),
                'scriptsApprovedToday' => Script::query()->whereDate('approved_at', $today)->count(),
                'sourcesPendingVerification' => SourceReference::query()->withoutArchived()->where('verification_status', 'pending')->count(),
                'weakSources' => SourceReference::query()->withoutArchived()->where('verification_status', 'weak')->count(),
                'missingSources' => SourceReference::query()->withoutArchived()->where('verification_status', 'missing')->count(),
                'brokenRejectedSources' => SourceReference::query()->withoutArchived()->whereIn('verification_status', ['broken', 'rejected'])->count(),
                'scriptsBlockedBySources' => Script::query()->where('status', '!=', 'archived')->whereHas('sourceReferences', fn (Builder $query) => $query->withoutArchived()->whereIn('verification_status', ['missing', 'broken', 'rejected']))->count(),
                'scriptsMissingMetadata' => Script::query()->where('status', '!=', 'archived')->where(function (Builder $query): void {
                    $query->where(function (Builder $q): void {
                        $q->whereNull('final_title')->whereNull('production_name');
                    })->orWhere(function (Builder $q): void {
                        $q->whereNull('public_description')->whereNull('short_description');
                    })->orWhere(function (Builder $q): void {
                        $q->where(function (Builder $i): void {
                            $i->whereNull('hashtags')->orWhereJsonLength('hashtags', 0);
                        });
                    })->orWhere(function (Builder $q): void {
                        $q->where(function (Builder $i): void {
                            $i->whereNull('target_platforms')->orWhereJsonLength('target_platforms', 0);
                        });
                    });
                })->count(),
                'scriptsReadyForProduction' => Script::query()->where('status', '!=', 'archived')->where('production_status', 'ready_for_production')->count(),
            ],
            'todayRuns' => $todayRunsQuery->orderBy('scheduled_for')->get(),
            'pendingPromptRuns' => $pendingPromptRuns,
            'waitingResponseRuns' => $waitingResponseRuns,
            'responseReceivedRuns' => $responseReceivedRuns,
            'scriptsNeedingReview' => $scriptsNeedingReview,
            'upcomingSchedules' => $upcomingSchedules,
            'readyToApprove' => $readyToApprove,
            'recentBulletinPromptRuns' => BulletinPromptRun::query()->where('status', '!=', 'archived')->with(['bulletinType:id,name'])->latest()->limit(10)->get(),
            'scriptsBlockedBySources' => $scriptsBlockedBySources,
        ]);
    }
}
