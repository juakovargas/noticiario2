<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Editor\DashboardController as EditorDashboardController;
use App\Http\Controllers\Editor\EditionController;
use App\Http\Controllers\Editor\EditionNewsItemController;
use App\Http\Controllers\Editor\EditorialTemplateController;
use App\Http\Controllers\Editor\LocationController;
use App\Http\Controllers\Editor\ScriptBuilderController;
use App\Http\Controllers\Editor\NewsCategoryController;
use App\Http\Controllers\Editor\NewsItemController;
use App\Http\Controllers\Editor\NewsSourceController;
use App\Http\Controllers\Editor\NewsSourceImportController;
use App\Http\Controllers\Editor\ScriptController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use App\Support\AuthRedirect;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
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

Route::middleware('auth')->post('/locale', [LocaleController::class, 'update'])->name('locale.update');

Route::middleware('auth')->post('/admin/impersonation/stop', [ImpersonationController::class, 'stop'])->name('admin.impersonation.stop');

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

        Route::post('/users/{user}/impersonate', [ImpersonationController::class, 'start'])->name('users.impersonate');

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

        Route::resource('languages', LanguageController::class)->except('show');
    });

Route::middleware(['auth', 'verified', 'permission:editor.access'])
    ->prefix('editor')
    ->name('editor.')
    ->group(function () {
        Route::get('/', fn () => redirect()->route('editor.dashboard'));

        Route::get('/dashboard', [EditorDashboardController::class, 'index'])
            ->middleware('permission:editor.dashboard.view')
            ->name('dashboard');

        Route::resource('locations', LocationController::class)->except('show');
        Route::resource('news-categories', NewsCategoryController::class)->except('show');
        Route::resource('news-sources', NewsSourceController::class)->except('show');
        Route::get('/news-sources/{newsSource}/import', [NewsSourceImportController::class, 'show'])->name('news-sources.import');
        Route::post('/news-sources/{newsSource}/import', [NewsSourceImportController::class, 'store'])->name('news-sources.import.store');
        Route::resource('news-items', NewsItemController::class);
        Route::resource('editions', EditionController::class);
        Route::get('/editions/{edition}/news-items', [EditionNewsItemController::class, 'index'])->name('editions.news-items.index');
        Route::post('/editions/{edition}/news-items', [EditionNewsItemController::class, 'store'])->name('editions.news-items.store');
        Route::put('/editions/{edition}/news-items/{newsItem}', [EditionNewsItemController::class, 'update'])->name('editions.news-items.update');
        Route::delete('/editions/{edition}/news-items/{newsItem}', [EditionNewsItemController::class, 'destroy'])->name('editions.news-items.destroy');
        Route::resource('scripts', ScriptController::class);
        Route::get('/editions/{edition}/script-builder', [ScriptBuilderController::class, 'create'])->name('editions.script-builder');
        Route::post('/editions/{edition}/script-builder', [ScriptBuilderController::class, 'store'])->name('editions.script-builder.store');

        Route::get('/audio', fn () => Inertia::render('Editor/Placeholder', [
            'title' => 'Audio',
            'description' => 'Audio module will be implemented in a future phase.',
        ]))->name('audio');

        Route::get('/video', fn () => Inertia::render('Editor/Placeholder', [
            'title' => 'Video',
            'description' => 'Video module will be implemented in a future phase.',
        ]))->name('video');

        Route::get('/media-renders', fn () => Inertia::render('Editor/Placeholder', [
            'title' => 'Media Renders',
            'description' => 'Media renders module will be implemented in a future phase.',
        ]))->name('media-renders');

        Route::get('/publications', fn () => Inertia::render('Editor/Placeholder', [
            'title' => 'Publications',
            'description' => 'Publications module will be implemented in a future phase.',
        ]))->name('publications');

        Route::get('/social-channels', fn () => Inertia::render('Editor/Placeholder', [
            'title' => 'Social Channels',
            'description' => 'Social channels module will be implemented in a future phase.',
        ]))->name('social-channels');

        Route::resource('editorial-templates', EditorialTemplateController::class);

        Route::get('/ai-prompt-templates', fn () => Inertia::render('Editor/Placeholder', [
            'title' => 'AI Prompt Templates',
            'description' => 'AI prompt templates module will be implemented in a future phase.',
        ]))->name('ai-prompt-templates');
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
