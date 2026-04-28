<?php

namespace App\Http\Controllers\Editor;

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
        $markers = $this->mapService->getEditorMapData();

        return Inertia::render('Editor/WorldMap/Index', [
            'markers' => $markers,
            'summary' => [
                'active_locations' => count($markers),
                'active_bulletin_types' => collect($markers)->sum('active_bulletin_count'),
                'pending_prompt_runs' => collect($markers)->sum('pending_prompt_runs_count'),
                'completed_prompt_runs' => collect($markers)->sum('completed_prompt_runs_count'),
            ],
        ]);
    }
}
