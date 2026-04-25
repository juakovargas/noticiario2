<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Editor\DashboardController as EditorDashboardController;
use App\Http\Controllers\Editor\PlaceholderPageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Viewer\DashboardController as ViewerDashboardController;
use App\Http\Controllers\Viewer\PublishedContentController;
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

        Route::get('/dashboard', EditorDashboardController::class)->name('dashboard');

        Route::controller(PlaceholderPageController::class)->group(function () {
            Route::get('/news-items', 'newsItems')->name('news-items.index');
            Route::get('/news-sources', 'newsSources')->name('news-sources.index');
            Route::get('/news-categories', 'newsCategories')->name('news-categories.index');
            Route::get('/locations', 'locations')->name('locations.index');

            Route::get('/editions', 'editions')->name('editions.index');
            Route::get('/scripts', 'scripts')->name('scripts.index');

            Route::get('/audio', 'audio')->name('audio.index');
            Route::get('/video', 'video')->name('video.index');
            Route::get('/media-renders', 'mediaRenders')->name('media-renders.index');

            Route::get('/publications', 'publications')->name('publications.index');
            Route::get('/social-channels', 'socialChannels')->name('social-channels.index');

            Route::get('/editorial-templates', 'editorialTemplates')->name('editorial-templates.index');
            Route::get('/ai-prompt-templates', 'aiPromptTemplates')->name('ai-prompt-templates.index');
        });
    });

Route::middleware(['auth', 'verified', 'permission:viewer.access'])
    ->prefix('viewer')
    ->name('viewer.')
    ->group(function () {
        Route::get('/', fn () => redirect()->route('viewer.dashboard'));

        Route::get('/dashboard', ViewerDashboardController::class)->name('dashboard');
        Route::get('/published-content', PublishedContentController::class)->name('published-content.index');
    });

require __DIR__.'/auth.php';
