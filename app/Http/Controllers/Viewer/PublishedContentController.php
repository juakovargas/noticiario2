<?php

namespace App\Http\Controllers\Viewer;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PublishedContentController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Viewer/PublishedContent/Index');
    }
}
