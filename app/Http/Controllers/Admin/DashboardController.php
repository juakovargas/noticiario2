<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'users' => User::query()->count(),
                'activeUsers' => User::query()->where('is_active', true)->count(),
                'inactiveUsers' => User::query()->where('is_active', false)->count(),
            ],
        ]);
    }
}
