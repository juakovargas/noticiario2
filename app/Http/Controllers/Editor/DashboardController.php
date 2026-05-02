<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Services\Editor\EditorDashboardOverviewService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(EditorDashboardOverviewService $service): Response
    {
        return Inertia::render('Editor/Dashboard', $service->build());
    }
}
