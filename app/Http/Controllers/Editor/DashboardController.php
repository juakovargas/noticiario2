<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Editor/Dashboard', [
            'stats' => [
                'editions' => 0,
                'newsItems' => 0,
                'scripts' => 0,
                'sources' => 0,
                'media' => 0,
            ],
        ]);
    }
}