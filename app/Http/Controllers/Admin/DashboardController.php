<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        $aiOverview = null;
        if ($hasAiProviders) {
            $providerModel = app(\App\Models\AiProvider::class);
            $aiOverview = [
                'total' => $providerModel::query()->count(),
                'active' => $providerModel::query()->where('is_active', true)->count(),
                'defaultProvider' => $providerModel::query()->where('is_default', true)->value('name'),
                'supportsWebSearch' => $providerModel::query()->where('supports_web_search', true)->count(),
                'supportsJsonMode' => $providerModel::query()->where('supports_json_mode', true)->count(),
            ];
        }

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'users' => User::query()->count(),
                'activeUsers' => Schema::hasColumn('users', 'is_active') ? User::query()->where('is_active', true)->count() : null,
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
            ],
            'recentFailedRuns' => Schema::hasTable('editorial_schedule_runs')
                ? EditorialScheduleRun::query()->with(['schedule:id,name'])->where('status', 'failed')->latest()->limit(8)->get()
                : [],
            'aiOverview' => $aiOverview,
            'canOpenEditorRun' => auth()->user()?->can('editor.access') ?? false,
        ]);
    }
}
