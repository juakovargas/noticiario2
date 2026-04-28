<?php

namespace App\Http\Controllers\Viewer;

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
        $markers = $this->mapService->getViewerMapData();

        return Inertia::render('Viewer/WorldMap/Index', [
            'markers' => $markers,
            'summary' => [
                'locations_on_map' => count($markers),
                'available_content' => collect($markers)->sum('completed_prompt_runs_count'),
            ],
        ]);
    }
}
