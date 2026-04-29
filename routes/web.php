<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\AiProviderController;
use App\Http\Controllers\Admin\AiRequestLogController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\MediaFileController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\HomePageSettingController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SeoSettingController;
use App\Http\Controllers\Admin\PromptProfileController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WorldMapController as AdminWorldMapController;
use App\Http\Controllers\Admin\BackgroundTaskController;
use App\Http\Controllers\Admin\OperationsDashboardController;
use App\Http\Controllers\Admin\OperationalEventController;
use App\Http\Controllers\Admin\ScriptViewerController;
use App\Http\Controllers\Admin\OperationAlertSettingController;
use App\Http\Controllers\Editor\AiPromptTemplateController;
use App\Http\Controllers\Editor\AiRequestLogController as EditorAiRequestLogController;
use App\Http\Controllers\Editor\DashboardController as EditorDashboardController;
use App\Http\Controllers\Editor\BulletinTypeController;
use App\Http\Controllers\Editor\BulletinPromptRunController;
use App\Http\Controllers\Editor\EditionController;
use App\Http\Controllers\Editor\EditionNewsItemController;
use App\Http\Controllers\Editor\EditorialDeskController;
use App\Http\Controllers\Editor\EditorialRequestController;
use App\Http\Controllers\Editor\EditorialScheduleController;
use App\Http\Controllers\Editor\EditorialScheduleRunController;
use App\Http\Controllers\Editor\EditorialTemplateController;
use App\Http\Controllers\Editor\EditorialWorkbenchController;
use App\Http\Controllers\Editor\LocationController;
use App\Http\Controllers\Editor\ScriptBuilderController;
use App\Http\Controllers\Editor\NewsCategoryController;
use App\Http\Controllers\Editor\NewsItemController;
use App\Http\Controllers\Editor\NewsSourceController;
use App\Http\Controllers\Editor\NewsSourceImportController;
use App\Http\Controllers\Editor\ScriptController;
use App\Http\Controllers\Editor\ScriptReviewController;
use App\Http\Controllers\InternalMessageController;
use App\Http\Controllers\Editor\ScriptProductionMetadataController;
use App\Http\Controllers\Editor\SourceReferenceController;
use App\Http\Controllers\Editor\WorldMapController as EditorWorldMapController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PreferenceController;
use App\Http\Controllers\Public\HomeController as PublicHomeController;
use App\Http\Controllers\Viewer\DashboardController as ViewerDashboardController;
use App\Http\Controllers\Viewer\WorldMapController as ViewerWorldMapController;
use App\Support\AuthRedirect;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


Route::get('/', [PublicHomeController::class, 'index'])->name('home');

if (! app()->isProduction()) {
    Route::get('/emergency-logout', function (Request $request) {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('emergency.logout');
}

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
    Route::patch('/preferences/appearance', [PreferenceController::class, 'updateAppearance'])->name('preferences.appearance.update');
});


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/messages', [InternalMessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{internalMessage}', [InternalMessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{internalMessage}/mark-read', [InternalMessageController::class, 'markRead'])->name('messages.mark-read');
    Route::post('/messages/{internalMessage}/archive', [InternalMessageController::class, 'archive'])->name('messages.archive');
});

Route::middleware(['auth', 'verified', 'permission:admin.access'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', fn () => redirect()->route('admin.dashboard'));

        Route::get('/dashboard', [AdminDashboardController::class, 'index'])
            ->middleware('permission:dashboard.view')
            ->name('dashboard');

        Route::get('/world-map', [AdminWorldMapController::class, 'index'])->name('world-map.index');

        Route::post('/users/{user}/impersonate', [ImpersonationController::class, 'start'])->name('users.impersonate');

        Route::get('/scripts/{script}', [ScriptViewerController::class, 'show'])->name('scripts.show');

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

        Route::resource('media-files', MediaFileController::class)
            ->only(['index', 'show', 'update']);

        Route::resource('languages', LanguageController::class)->except('show');
        Route::resource('ai-providers', AiProviderController::class);
        Route::get('/ai-request-logs', [AiRequestLogController::class, 'index'])->name('ai-request-logs.index');
        Route::get('/ai-request-logs/{aiRequestLog}', [AiRequestLogController::class, 'show'])->name('ai-request-logs.show');
        Route::resource('prompt-profiles', PromptProfileController::class);

        Route::get('/seo-settings', [SeoSettingController::class, 'edit'])->name('seo-settings.edit');
        Route::put('/seo-settings', [SeoSettingController::class, 'update'])->name('seo-settings.update');
        Route::resource('home-page-settings', HomePageSettingController::class)->except('show');
        Route::get('/background-tasks', [BackgroundTaskController::class, 'index'])->name('background-tasks.index');
        Route::get('/background-tasks/{backgroundTask}', [BackgroundTaskController::class, 'show'])->name('background-tasks.show');

        Route::get('/operations', [OperationsDashboardController::class, 'index'])->name('operations.index');
        Route::get('/operations/summary', [OperationsDashboardController::class, 'summaryData'])->name('operations.summary');
        Route::get('/operations/events', [OperationalEventController::class, 'index'])->name('operations.events.index');
        Route::get('/operations/events/{operationalEvent}', [OperationalEventController::class, 'show'])->name('operations.events.show');
        Route::get('/operation-alert-settings', [OperationAlertSettingController::class, 'index'])->name('operation-alert-settings.index');
        Route::get('/operation-alert-settings/{operationAlertSetting}/edit', [OperationAlertSettingController::class, 'edit'])->name('operation-alert-settings.edit');
        Route::put('/operation-alert-settings/{operationAlertSetting}', [OperationAlertSettingController::class, 'update'])->name('operation-alert-settings.update');

    });

Route::middleware(['auth', 'verified', 'permission:editor.access'])
    ->prefix('editor')
    ->name('editor.')
    ->group(function () {
        Route::get('/', fn () => redirect()->route('editor.dashboard'));

        Route::get('/dashboard', [EditorDashboardController::class, 'index'])
            ->middleware('permission:editor.dashboard.view')
            ->name('dashboard');

        Route::get('/world-map', [EditorWorldMapController::class, 'index'])->name('world-map.index');

        Route::get('/workbench', [EditorialWorkbenchController::class, 'index'])->name('workbench');

        Route::resource('locations', LocationController::class)->except('show');
        Route::resource('news-categories', NewsCategoryController::class)->except('show');
        Route::resource('news-sources', NewsSourceController::class)->except('show');
        Route::get('/news-sources/{newsSource}/import', [NewsSourceImportController::class, 'show'])->name('news-sources.import');
        Route::post('/news-sources/{newsSource}/import', [NewsSourceImportController::class, 'store'])->name('news-sources.import.store');
        Route::resource('news-items', NewsItemController::class);
        Route::post('/news-items/{newsItem}/archive', [NewsItemController::class, 'archive'])->name('news-items.archive');
        Route::post('/news-items/{newsItem}/restore', [NewsItemController::class, 'restore'])->name('news-items.restore');
        Route::resource('editions', EditionController::class);
        Route::post('/editions/{edition}/archive', [EditionController::class, 'archive'])->name('editions.archive');
        Route::post('/editions/{edition}/restore', [EditionController::class, 'restore'])->name('editions.restore');
        Route::get('/editions/{edition}/news-items', [EditionNewsItemController::class, 'index'])->name('editions.news-items.index');
        Route::post('/editions/{edition}/news-items', [EditionNewsItemController::class, 'store'])->name('editions.news-items.store');
        Route::put('/editions/{edition}/news-items/{newsItem}', [EditionNewsItemController::class, 'update'])->name('editions.news-items.update');
        Route::delete('/editions/{edition}/news-items/{newsItem}', [EditionNewsItemController::class, 'destroy'])->name('editions.news-items.destroy');
        Route::resource('scripts', ScriptController::class);
        Route::post('/scripts/{script}/archive', [ScriptController::class, 'archive'])->name('scripts.archive');
        Route::post('/scripts/{script}/restore', [ScriptController::class, 'restore'])->name('scripts.restore');
        Route::get('/scripts/{script}/production', [ScriptProductionMetadataController::class, 'edit'])->name('scripts.production.edit');
        Route::put('/scripts/{script}/production', [ScriptProductionMetadataController::class, 'update'])->name('scripts.production.update');
        Route::get('/source-references', [SourceReferenceController::class, 'index'])->name('source-references.index');
        Route::get('/source-references/{sourceReference}', [SourceReferenceController::class, 'show'])->name('source-references.show');
        Route::put('/source-references/{sourceReference}', [SourceReferenceController::class, 'update'])->name('source-references.update');
        Route::post('/bulletin-prompt-runs/{bulletinPromptRun}/source-references/extract', [SourceReferenceController::class, 'extractFromBulletinPromptRun'])->name('bulletin-prompt-runs.source-references.extract');
        Route::post('/scripts/{script}/source-references/extract', [SourceReferenceController::class, 'extractFromScript'])->name('scripts.source-references.extract');
        Route::post('/script-review-items/{scriptReviewItem}/source-references/extract', [SourceReferenceController::class, 'extractFromScriptReviewItem'])->name('script-review-items.source-references.extract');
        Route::post('/news-items/{newsItem}/source-references/extract', [SourceReferenceController::class, 'extractFromNewsItem'])->name('news-items.source-references.extract');
        Route::get('/scripts/{script}/review', [ScriptReviewController::class, 'show'])->name('scripts.review');
        Route::post('/scripts/{script}/review/generate-items', [ScriptReviewController::class, 'generateItems'])->name('scripts.review.generate-items');
        Route::put('/scripts/{script}/review', [ScriptReviewController::class, 'update'])->name('scripts.review.update');
        Route::put('/scripts/{script}/review-items/{scriptReviewItem}', [ScriptReviewController::class, 'updateItem'])->name('scripts.review-items.update');
        Route::post('/scripts/{script}/review/mark-in-review', [ScriptReviewController::class, 'markInReview'])->name('scripts.review.mark-in-review');
        Route::post('/scripts/{script}/review/mark-verified', [ScriptReviewController::class, 'markVerified'])->name('scripts.review.mark-verified');
        Route::post('/scripts/{script}/review/approve', [ScriptReviewController::class, 'approve'])->name('scripts.review.approve');
        Route::post('/scripts/{script}/review/reject', [ScriptReviewController::class, 'reject'])->name('scripts.review.reject');
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
        Route::resource('ai-prompt-templates', AiPromptTemplateController::class);
        Route::get('/ai-request-logs/{aiRequestLog}', [EditorAiRequestLogController::class, 'show'])->name('ai-request-logs.show');
        Route::resource('editorial-requests', EditorialRequestController::class);
        Route::post('/editorial-requests/{editorialRequest}/archive', [EditorialRequestController::class, 'archive'])->name('editorial-requests.archive');
        Route::post('/editorial-requests/{editorialRequest}/restore', [EditorialRequestController::class, 'restore'])->name('editorial-requests.restore');
        Route::post('/editorial-requests/{editorialRequest}/run', [EditorialRequestController::class, 'run'])->name('editorial-requests.run');
        Route::post('/editorial-requests/{editorialRequest}/create-news-items', [EditorialRequestController::class, 'createNewsItems'])->name('editorial-requests.create-news-items');
        Route::post('/editorial-requests/{editorialRequest}/convert-to-edition', [EditorialRequestController::class, 'convertToEdition'])->name('editorial-requests.convert-to-edition');
        Route::get('/editorial-desk', [EditorialDeskController::class, 'index'])->name('editorial-desk.index');
        Route::resource('editorial-schedules', EditorialScheduleController::class);
        Route::resource('bulletin-types', BulletinTypeController::class);
        Route::resource('bulletin-prompt-runs', BulletinPromptRunController::class)->only(['index', 'show']);
        Route::post('/bulletin-types/{bulletinType}/prompt-runs', [BulletinPromptRunController::class, 'store'])->name('bulletin-types.prompt-runs.store');
        Route::put('/bulletin-prompt-runs/{bulletinPromptRun}/schedule', [BulletinPromptRunController::class, 'updateSchedule'])->name('bulletin-prompt-runs.update-schedule');
        Route::post('/bulletin-prompt-runs/{bulletinPromptRun}/generate-prompt', [BulletinPromptRunController::class, 'generatePrompt'])->name('bulletin-prompt-runs.generate-prompt');
        Route::post('/bulletin-prompt-runs/{bulletinPromptRun}/generate-prompt-queued', [BulletinPromptRunController::class, 'generatePromptQueued'])->name('bulletin-prompt-runs.generate-prompt-queued');
        Route::post('/bulletin-prompt-runs/{bulletinPromptRun}/generate-ai-response', [BulletinPromptRunController::class, 'generateAiResponse'])->name('bulletin-prompt-runs.generate-ai-response');
        Route::post('/bulletin-prompt-runs/{bulletinPromptRun}/generate-ai-response-queued', [BulletinPromptRunController::class, 'generateAiResponseQueued'])->name('bulletin-prompt-runs.generate-ai-response-queued');
        Route::post('/bulletin-prompt-runs/{bulletinPromptRun}/source-references/extract-queued', [BulletinPromptRunController::class, 'extractSourcesQueued'])->name('bulletin-prompt-runs.source-references.extract-queued');
        Route::post('/bulletin-prompt-runs/{bulletinPromptRun}/save-response', [BulletinPromptRunController::class, 'saveResponse'])->name('bulletin-prompt-runs.save-response');
        Route::post('/bulletin-prompt-runs/{bulletinPromptRun}/create-script', [BulletinPromptRunController::class, 'createScript'])->name('bulletin-prompt-runs.create-script');
        Route::post('/bulletin-prompt-runs/{bulletinPromptRun}/run-pipeline', [BulletinPromptRunController::class, 'runPipeline'])->name('bulletin-prompt-runs.run-pipeline');
        Route::post('/bulletin-prompt-runs/{bulletinPromptRun}/retry-pipeline', [BulletinPromptRunController::class, 'retryPipeline'])->name('bulletin-prompt-runs.retry-pipeline');
        Route::post('/bulletin-prompt-runs/{bulletinPromptRun}/archive', [BulletinPromptRunController::class, 'archive'])->name('bulletin-prompt-runs.archive');
        Route::post('/bulletin-prompt-runs/{bulletinPromptRun}/restore', [BulletinPromptRunController::class, 'restore'])->name('bulletin-prompt-runs.restore');
        Route::post('/bulletin-prompt-runs/{bulletinPromptRun}/mark-completed', [BulletinPromptRunController::class, 'markCompleted'])->name('bulletin-prompt-runs.mark-completed');
        Route::post('/bulletin-prompt-runs/{bulletinPromptRun}/cancel', [BulletinPromptRunController::class, 'cancel'])->name('bulletin-prompt-runs.cancel');
        Route::resource('editorial-schedule-runs', EditorialScheduleRunController::class)->only(['index', 'show']);
        Route::post('/editorial-schedule-runs/{editorialScheduleRun}/archive', [EditorialScheduleRunController::class, 'archive'])->name('editorial-schedule-runs.archive');
        Route::post('/editorial-schedule-runs/{editorialScheduleRun}/restore', [EditorialScheduleRunController::class, 'restore'])->name('editorial-schedule-runs.restore');
        Route::post('/editorial-schedules/{editorialSchedule}/runs', [EditorialScheduleController::class, 'createRun'])->name('editorial-schedules.runs.store');
        Route::post('/editorial-schedules/{editorialSchedule}/run-now', [EditorialScheduleController::class, 'runNow'])->name('editorial-schedules.run-now');
        Route::post('/editorial-schedules/{editorialSchedule}/recalculate-next-run', [EditorialScheduleController::class, 'recalculateNextRun'])->name('editorial-schedules.recalculate-next-run');
        Route::post('/editorial-schedule-runs/{editorialScheduleRun}/generate-prompt', [EditorialScheduleRunController::class, 'generatePrompt'])->name('editorial-schedule-runs.generate-prompt');
        Route::post('/editorial-schedule-runs/{editorialScheduleRun}/receive-response', [EditorialScheduleRunController::class, 'receiveResponse'])->name('editorial-schedule-runs.receive-response');
        Route::post('/editorial-schedule-runs/{editorialScheduleRun}/create-script', [EditorialScheduleRunController::class, 'createScript'])->name('editorial-schedule-runs.create-script');
        Route::post('/editorial-schedule-runs/{editorialScheduleRun}/complete', [EditorialScheduleRunController::class, 'complete'])->name('editorial-schedule-runs.complete');
        Route::post('/source-references/{sourceReference}/archive', [SourceReferenceController::class, 'archive'])->name('source-references.archive');
        Route::post('/source-references/{sourceReference}/restore', [SourceReferenceController::class, 'restore'])->name('source-references.restore');
    });

Route::middleware(['auth', 'verified', 'permission:viewer.access'])
    ->prefix('viewer')
    ->name('viewer.')
    ->group(function () {
        Route::get('/', fn () => redirect()->route('viewer.dashboard'));

        Route::get('/dashboard', [ViewerDashboardController::class, 'index'])
            ->middleware('permission:viewer.dashboard.view')
            ->name('dashboard');

        Route::get('/world-map', [ViewerWorldMapController::class, 'index'])->name('world-map.index');

        Route::get('/published-content', fn () => Inertia::render('Viewer/PublishedContent'))
            ->name('published-content');
    });

require __DIR__.'/auth.php';
