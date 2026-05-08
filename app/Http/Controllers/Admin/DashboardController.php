<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiRequestLog;
use App\Models\AiProvider;
use App\Models\Edition;
use App\Models\EditorialSchedule;
use App\Models\EditorialScheduleRun;
use App\Models\Script;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $today = now()->toDateString();
        $hasAiProviders = Schema::hasTable('ai_providers');
        $hasUserStatus = Schema::hasColumn('users', 'is_active');
        $hasGoogleUsers = Schema::hasColumn('users', 'google_id');

        $userStats = [
            'total' => User::query()->count(),
            'active' => $hasUserStatus ? User::query()->where('is_active', true)->count() : null,
            'inactive' => $hasUserStatus ? User::query()->where('is_active', false)->count() : null,
            'google' => $hasGoogleUsers ? User::query()->whereNotNull('google_id')->count() : null,
            'password' => $hasGoogleUsers ? User::query()->whereNull('google_id')->count() : null,
            'newLastSevenDays' => User::query()->where('created_at', '>=', now()->subDays(7))->count(),
        ];

        $aiOverview = null;
        if ($hasAiProviders) {
            $providerModel = app(\App\Models\AiProvider::class);
            $aiOverview = [
                'total' => $providerModel::query()->count(),
                'active' => $providerModel::query()->where('is_active', true)->count(),
                'defaultProvider' => $providerModel::query()->where('is_default', true)->value('name'),
            ];
        }

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'users' => $userStats['total'],
                'activeUsers' => $userStats['active'],
                'roles' => Role::query()->count(),
                'permissions' => Permission::query()->count(),
                'languages' => Schema::hasTable('languages') ? \App\Models\Language::query()->count() : 0,
                'activeSchedules' => Schema::hasTable('editorial_schedules') ? EditorialSchedule::query()->where('is_active', true)->count() : 0,
                'todayRuns' => Schema::hasTable('editorial_schedule_runs') ? EditorialScheduleRun::query()->whereDate('scheduled_for', $today)->count() : 0,
                'failedRuns' => Schema::hasTable('editorial_schedule_runs') ? EditorialScheduleRun::query()->where('status', 'failed')->count() : 0,
                'draftScripts' => Schema::hasTable('scripts') ? Script::query()->whereIn('status', ['draft', 'review'])->count() : 0,
                'plannedEditions' => Schema::hasTable('editions') ? Edition::query()->whereIn('status', ['planning', 'scripting'])->count() : 0,
                'aiProviders' => $aiOverview['total'] ?? null,
                'activeAiProviders' => $aiOverview['active'] ?? null,
                'aiRequestsToday' => Schema::hasTable('ai_request_logs') ? AiRequestLog::query()->whereDate('created_at', $today)->count() : 0,
                'failedAiRequests' => Schema::hasTable('ai_request_logs') ? AiRequestLog::query()->whereDate('created_at', $today)->where('status', 'failed')->count() : 0,
                'aiEstimatedCostToday' => Schema::hasTable('ai_request_logs') ? (float) AiRequestLog::query()->whereDate('created_at', $today)->sum('estimated_cost') : 0,
                'aiEstimatedCostMonth' => Schema::hasTable('ai_request_logs') ? (float) AiRequestLog::query()->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('estimated_cost') : 0,
                'blockedAiRequests' => Schema::hasTable('ai_request_logs') ? AiRequestLog::query()->whereDate('created_at', $today)->where('limit_blocked', true)->count() : 0,
                'missingEnvProviders' => Schema::hasTable('ai_providers') ? AiProvider::query()->get()->filter(fn (AiProvider $provider) => ! $provider->hasConfiguredApiKey())->count() : 0,
            ],
            'userStats' => $userStats,
            'recentFailedRuns' => Schema::hasTable('editorial_schedule_runs')
                ? EditorialScheduleRun::query()->with(['schedule:id,name'])->where('status', 'failed')->latest()->limit(8)->get()
                : [],
            'aiOverview' => $aiOverview,
            'latestFailedAiRequestId' => Schema::hasTable('ai_request_logs') ? AiRequestLog::query()->where('status', 'failed')->latest()->value('id') : null,
            'canOpenEditorRun' => auth()->user()?->can('editor.access') ?? false,
        ]);
    }
}
