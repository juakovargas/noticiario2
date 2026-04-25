<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Editor/Dashboard');
    }
}
