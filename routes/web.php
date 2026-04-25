<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Editor\DashboardController as EditorDashboardController;
use App\Http\Controllers\ProfileController;
use App\Support\AuthRedirect;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    $user = request()->user();

    if (! $user) {
        abort(403);
    }

    $routeName = AuthRedirect::routeNameFor($user);

    if ($routeName === 'dashboard') {
        abort(403);
    }

    return redirect()->route($routeName);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'permission:admin.access'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', fn () => redirect()->route('admin.dashboard'));

        Route::get('/dashboard', [AdminDashboardController::class, 'index'])
            ->middleware('permission:dashboard.view')
            ->name('dashboard');

        Route::resource('users', UserController::class)
            ->middleware([
                'index' => 'permission:users.view',
                'create' => 'permission:users.create',
                'store' => 'permission:users.create',
                'show' => 'permission:users.view',
                'edit' => 'permission:users.update',
                'update' => 'permission:users.update',
                'destroy' => 'permission:users.delete',
            ]);

        Route::resource('roles', RoleController::class)
            ->except('show')
            ->middleware([
                'index' => 'permission:roles.view',
                'create' => 'permission:roles.create',
                'store' => 'permission:roles.create',
                'edit' => 'permission:roles.update',
                'update' => 'permission:roles.update',
                'destroy' => 'permission:roles.delete',
            ]);

        Route::resource('permissions', PermissionController::class)
            ->except('show')
            ->middleware([
                'index' => 'permission:permissions.view',
                'create' => 'permission:permissions.create',
                'store' => 'permission:permissions.create',
                'edit' => 'permission:permissions.update',
                'update' => 'permission:permissions.update',
                'destroy' => 'permission:permissions.delete',
            ]);
    });

Route::middleware(['auth', 'verified', 'permission:editor.access'])
    ->prefix('editor')
    ->name('editor.')
    ->group(function () {
        Route::get('/', fn () => redirect()->route('editor.dashboard'));

        Route::get('/dashboard', [EditorDashboardController::class, 'index'])
            ->middleware('permission:editor.dashboard.view')
            ->name('dashboard');

        Route::get('/editions', fn () => Inertia::render('Editor/Placeholder', [
            'title' => 'Editions',
            'description' => 'Editions module will be implemented in the next phase.',
        ]))->name('editions');

        Route::get('/news-items', fn () => Inertia::render('Editor/Placeholder', [
            'title' => 'News Items',
            'description' => 'News Items module will be implemented in the next phase.',
        ]))->name('news-items');

        Route::get('/scripts', fn () => Inertia::render('Editor/Placeholder', [
            'title' => 'Scripts',
            'description' => 'Scripts module will be implemented in the next phase.',
        ]))->name('scripts');

        Route::get('/sources', fn () => Inertia::render('Editor/Placeholder', [
            'title' => 'Sources',
            'description' => 'Sources module will be implemented in the next phase.',
        ]))->name('sources');

        Route::get('/media', fn () => Inertia::render('Editor/Placeholder', [
            'title' => 'Media',
            'description' => 'Media module will be implemented in the next phase.',
        ]))->name('media');
    });

Route::middleware(['auth', 'verified', 'permission:viewer.access'])
    ->prefix('viewer')
    ->name('viewer.')
    ->group(function () {
        Route::get('/', fn () => redirect()->route('viewer.dashboard'));

        Route::get('/dashboard', fn () => Inertia::render('Viewer/Dashboard'))
            ->middleware('permission:viewer.dashboard.view')
            ->name('dashboard');

        Route::get('/published-content', fn () => Inertia::render('Viewer/PublishedContent'))
            ->name('published-content');
    });

require __DIR__.'/auth.php';