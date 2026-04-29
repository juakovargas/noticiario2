<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\AiRequestLog;
use Inertia\Inertia;
use Inertia\Response;

class AiRequestLogController extends Controller
{
    public function show(AiRequestLog $aiRequestLog): Response
    {
        $user = request()->user();
        $run = $aiRequestLog->bulletinPromptRun;

        $allowed = ($run && $run->created_by === $user?->id) || $aiRequestLog->user_id === $user?->id;
        abort_unless($allowed, 403, 'You do not have permission to view this AI request log');

        $aiRequestLog->loadMissing(['provider:id,name,provider_type', 'bulletinPromptRun:id,title']);

        return Inertia::render('Editor/AiRequestLogs/Show', [
            'log' => [
                'id' => $aiRequestLog->id,
                'status' => $aiRequestLog->status,
                'request_type' => $aiRequestLog->request_type,
                'model' => $aiRequestLog->model,
                'prompt_preview' => $aiRequestLog->prompt_preview,
                'response_preview' => $aiRequestLog->response_preview,
                'input_tokens' => $aiRequestLog->input_tokens,
                'output_tokens' => $aiRequestLog->output_tokens,
                'total_tokens' => $aiRequestLog->total_tokens,
                'estimated_cost' => $aiRequestLog->estimated_cost,
                'duration_ms' => $aiRequestLog->duration_ms,
                'error_code' => $aiRequestLog->error_code,
                'error_message' => $aiRequestLog->error_message,
                'provider_status_code' => $aiRequestLog->provider_status_code,
                'started_at' => optional($aiRequestLog->started_at)?->toDateTimeString(),
                'completed_at' => optional($aiRequestLog->completed_at)?->toDateTimeString(),
                'provider' => $aiRequestLog->provider,
                'bulletin_prompt_run' => $aiRequestLog->bulletinPromptRun,
                'metadata' => $aiRequestLog->metadata,
            ],
        ]);
    }
}
