<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Maps\BulletinMapService;
use Inertia\Inertia;
use Inertia\Response;

class WorldMapController extends Controller
{
    public function __construct(private readonly BulletinMapService $mapService)
    {
    }

    public function index(): Response
    {
        $markers = $this->mapService->getAdminMapData();

        return Inertia::render('Admin/WorldMap/Index', [
            'markers' => $markers,
            'summary' => [
                'mapped_locations' => count($markers),
                'active_bulletin_types' => collect($markers)->sum('active_bulletin_count'),
                'pending_prompt_runs' => collect($markers)->sum('pending_prompt_runs_count'),
                'completed_prompt_runs' => collect($markers)->sum('completed_prompt_runs_count'),
            ],
        ]);
    }
}
