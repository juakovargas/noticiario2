<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\NewsSource;
use App\Services\NewsIngestion\NewsItemImporter;
use App\Services\NewsIngestion\RssFeedReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NewsSourceImportController extends Controller
{
    public function __construct(
        private readonly RssFeedReader $feedReader,
        private readonly NewsItemImporter $importer,
    ) {
    }

    public function show(NewsSource $newsSource): Response|RedirectResponse
    {
        if ($error = $this->validateImportable($newsSource)) {
            return to_route('editor.news-sources.index')->with('error', $error);
        }

        $items = $this->feedReader->preview((string) $newsSource->feed_url, 30);

        if ($items === []) {
            return to_route('editor.news-sources.index')->with('error', 'Feed could not be read');
        }

        $newsSource->forceFill(['last_checked_at' => now()])->save();

        return Inertia::render('Editor/NewsSources/Import', [
            'source' => $this->sourcePayload($newsSource->fresh(['defaultCategory:id,name', 'defaultLocation:id,name'])),
            'items' => $this->importer->previewDuplicates($newsSource, $items),
        ]);
    }

    public function store(Request $request, NewsSource $newsSource): RedirectResponse
    {
        if ($error = $this->validateImportable($newsSource)) {
            return to_route('editor.news-sources.index')->with('error', $error);
        }

        $validated = $request->validate([
            'selected_keys' => ['required', 'array', 'min:1'],
            'selected_keys.*' => ['required'],
        ]);

        $items = $this->feedReader->preview((string) $newsSource->feed_url, 30);

        if ($items === []) {
            return back()->with('error', 'Feed could not be read');
        }

        $results = $this->importer->importSelected($newsSource, $items, $validated['selected_keys']);

        $updates = ['last_checked_at' => now()];
        if ($results['imported'] > 0) {
            $updates['last_imported_at'] = now();
        }
        $newsSource->forceFill($updates)->save();

        if ($results['failed'] > 0) {
            return to_route('editor.news-items.index')
                ->with('warning', "Imported {$results['imported']} news items. Skipped {$results['skipped_duplicates']} duplicates. Failed {$results['failed']} items.");
        }

        return to_route('editor.news-items.index')
            ->with('success', "Imported {$results['imported']} news items. Skipped {$results['skipped_duplicates']} duplicates.");
    }

    private function validateImportable(NewsSource $source): ?string
    {
        if (! $source->is_active || $source->type !== 'rss' || ! $source->feed_url) {
            return 'This source cannot be imported';
        }

        return null;
    }

    private function sourcePayload(NewsSource $source): array
    {
        return [
            'id' => $source->id,
            'name' => $source->name,
            'type' => $source->type,
            'feed_url' => $source->feed_url,
            'language' => $source->language,
            'default_category' => $source->defaultCategory?->name,
            'default_location' => $source->defaultLocation?->name,
            'last_checked_at' => $source->last_checked_at?->toDateTimeString(),
            'last_imported_at' => $source->last_imported_at?->toDateTimeString(),
        ];
    }
}
