<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\BulletinPromptRun;
use App\Models\BulletinType;
use App\Models\Script;
use App\Models\EditorialSchedule;
use App\Models\EditorialScheduleRun;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class EditorialWorkbenchController extends Controller
{
    public function index(): Response
    {
        $activeRuns = BulletinPromptRun::query()->where('status', '!=', 'archived');
        $activeScripts = Script::query()->where('status', '!=', 'archived');

        $readyForResponseRuns = (clone $activeRuns)
            ->with(['bulletinType:id,name'])
            ->whereIn('status', ['prompt_ready', 'waiting_ai_response'])
            ->latest('updated_at')
            ->limit(8)
            ->get();

        $readyForScriptRuns = (clone $activeRuns)
            ->with(['bulletinType:id,name'])
            ->where('status', 'response_received')
            ->whereNull('script_id')
            ->latest('updated_at')
            ->limit(8)
            ->get();

        $scriptsNeedingReview = (clone $activeScripts)
            ->with(['bulletinPromptRun:id,title'])
            ->whereIn('review_status', ['pending', 'in_review', 'needs_sources', 'needs_changes'])
            ->latest('updated_at')
            ->limit(8)
            ->get();

        $scriptsMissingMetadata = (clone $activeScripts)
            ->with(['bulletinPromptRun:id,title'])
            ->where(function (Builder $query): void {
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
            })
            ->latest('updated_at')
            ->limit(8)
            ->get();

        $scriptsReadyForProduction = (clone $activeScripts)
            ->with(['bulletinPromptRun:id,title'])
            ->where('production_status', 'ready_for_production')
            ->latest('ready_for_production_at')
            ->limit(8)
            ->get();

        return Inertia::render('Editor/Workbench/Index', [
            'steps' => [
                'activeBulletinTypes' => BulletinType::query()->where('is_active', true)->orderBy('name')->limit(6)->get(['id', 'name', 'slug']),
                'draftRuns' => (clone $activeRuns)->with('bulletinType:id,name')->where('status', 'draft')->latest('updated_at')->limit(6)->get(),
                'readyForResponseRuns' => $readyForResponseRuns,
                'readyForScriptRuns' => $readyForScriptRuns,
                'scriptsNeedingReview' => $scriptsNeedingReview,
                'scriptsReadyForProduction' => $scriptsReadyForProduction,
            ],
            'cards' => [
                'due_schedules' => EditorialSchedule::query()->where('is_active', true)->whereNotNull('next_run_at')->where('next_run_at', '<=', now())->count(),
                'upcoming_schedules' => EditorialSchedule::query()->where('is_active', true)->whereNotNull('next_run_at')->where('next_run_at', '>', now())->count(),
                'prompt_runs_waiting_for_response' => (clone $activeRuns)->whereIn('status', ['prompt_ready', 'waiting_ai_response'])->count(),
                'responses_ready_to_become_scripts' => (clone $activeRuns)->where('status', 'response_received')->whereNull('script_id')->count(),
                'scripts_needing_review' => (clone $activeScripts)->whereIn('review_status', ['pending', 'in_review', 'needs_sources', 'needs_changes'])->count(),
                'scripts_missing_metadata' => $scriptsMissingMetadata->count(),
                'scripts_ready_for_production' => (clone $activeScripts)->where('production_status', 'ready_for_production')->count(),
                'source_issues_pending' => (clone $activeScripts)->whereHas('sourceReferences', fn (Builder $query) => $query->whereNull('archived_at')->whereIn('verification_status', ['missing', 'broken', 'rejected', 'weak']))->count(),
            ],
            'lists' => [
                'dueSchedules' => EditorialSchedule::query()->where('is_active', true)->whereNotNull('next_run_at')->where('next_run_at', '<=', now())->orderBy('next_run_at')->limit(6)->get(['id','name','next_run_at']),
                'recentScheduleRuns' => EditorialScheduleRun::query()->with('schedule:id,name')->latest()->limit(6)->get(['id','editorial_schedule_id','status','created_at']),
                'readyForResponseRuns' => $readyForResponseRuns,
                'readyForScriptRuns' => $readyForScriptRuns,
                'scriptsNeedingReview' => $scriptsNeedingReview,
                'scriptsMissingMetadata' => $scriptsMissingMetadata,
                'scriptsReadyForProduction' => $scriptsReadyForProduction,
            ],
        ]);
    }
}
