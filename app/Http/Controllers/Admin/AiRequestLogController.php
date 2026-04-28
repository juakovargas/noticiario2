<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiProvider;
use App\Models\AiRequestLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AiRequestLogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'provider' => (string) $request->query('provider', ''),
            'status' => (string) $request->query('status', ''),
            'user' => (string) $request->query('user', ''),
            'model' => (string) $request->query('model', ''),
            'request_type' => (string) $request->query('request_type', ''),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'has_error' => (string) $request->query('has_error', ''),
            'limit_blocked' => (string) $request->query('limit_blocked', ''),
            'search' => (string) $request->query('search', ''),
        ];

        $logs = AiRequestLog::query()
            ->with(['provider:id,name', 'user:id,name', 'bulletinPromptRun:id,title'])
            ->when($filters['provider'] !== '', fn (Builder $q) => $q->where('ai_provider_id', $filters['provider']))
            ->when($filters['status'] !== '', fn (Builder $q) => $q->where('status', $filters['status']))
            ->when($filters['user'] !== '', fn (Builder $q) => $q->where('user_id', $filters['user']))
            ->when($filters['model'] !== '', fn (Builder $q) => $q->where('model', 'like', '%'.$filters['model'].'%'))
            ->when($filters['request_type'] !== '', fn (Builder $q) => $q->where('request_type', $filters['request_type']))
            ->when($filters['date_from'] !== '', fn (Builder $q) => $q->whereDate('created_at', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn (Builder $q) => $q->whereDate('created_at', '<=', $filters['date_to']))
            ->when($filters['has_error'] !== '', fn (Builder $q) => $q->whereNotNull('error_message'))
            ->when($filters['limit_blocked'] !== '', fn (Builder $q) => $q->where('limit_blocked', filter_var($filters['limit_blocked'], FILTER_VALIDATE_BOOLEAN)))
            ->when($filters['search'] !== '', fn (Builder $q) => $q->where(function (Builder $sq) use ($filters): void {
                $term = $filters['search'];
                $sq->where('prompt_preview', 'like', "%{$term}%")
                    ->orWhere('response_preview', 'like', "%{$term}%")
                    ->orWhere('error_message', 'like', "%{$term}%")
                    ->orWhere('model', 'like', "%{$term}%");
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/AiRequestLogs/Index', [
            'logs' => $logs,
            'filters' => $filters,
            'providers' => AiProvider::query()->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => ['pending', 'success', 'failed'],
            'requestTypes' => ['bulletin_prompt_run'],
            'summary' => $this->summary(),
        ]);
    }

    public function show(AiRequestLog $aiRequestLog): Response
    {
        $aiRequestLog->load(['provider:id,name,provider_type', 'user:id,name,email', 'bulletinPromptRun:id,title']);

        return Inertia::render('Admin/AiRequestLogs/Show', [
            'log' => $aiRequestLog,
        ]);
    }

    private function summary(): array
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        return [
            'requests_today' => AiRequestLog::query()->whereDate('created_at', $today)->count(),
            'successful_today' => AiRequestLog::query()->whereDate('created_at', $today)->where('status', 'success')->count(),
            'failed_today' => AiRequestLog::query()->whereDate('created_at', $today)->where('status', 'failed')->count(),
            'blocked_today' => AiRequestLog::query()->whereDate('created_at', $today)->where('limit_blocked', true)->count(),
            'estimated_cost_today' => (float) AiRequestLog::query()->whereDate('created_at', $today)->sum('estimated_cost'),
            'estimated_cost_month' => (float) AiRequestLog::query()->whereBetween('created_at', [$monthStart, $monthEnd])->sum('estimated_cost'),
            'average_duration_ms' => (int) round((float) AiRequestLog::query()->whereNotNull('duration_ms')->avg('duration_ms')),
        ];
    }
}
