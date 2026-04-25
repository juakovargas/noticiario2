<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\NewsItem;
use App\Models\NewsSource;
use App\Models\Script;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Editor/Dashboard', [
            'stats' => [
                'editions' => Edition::query()->count(),
                'newsItems' => NewsItem::query()->count(),
                'scripts' => Script::query()->count(),
                'sources' => NewsSource::query()->count(),
            ],
        ]);
    }
}
