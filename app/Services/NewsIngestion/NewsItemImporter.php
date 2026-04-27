<?php

namespace App\Services\NewsIngestion;

use App\Models\Location;
use App\Models\NewsItem;
use App\Models\NewsSource;
use App\Support\EditorialLanguage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class NewsItemImporter
{
    public function __construct(private readonly EditorialLanguage $editorialLanguage)
    {
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    public function previewDuplicates(NewsSource $source, array $items): array
    {
        return collect($items)->values()->map(function (array $item, int $index) use ($source): array {
            $externalId = $this->clean($item['external_id'] ?? null);
            $sourceUrl = $this->clean($item['source_url'] ?? null);
            $importedHash = $this->importedHash($item);

            $reason = $this->duplicateReason($source, $externalId, $sourceUrl, $importedHash);

            return [
                ...$item,
                'key' => (string) $index,
                'external_id' => $externalId,
                'source_url' => $sourceUrl,
                'imported_hash' => $importedHash,
                'is_duplicate' => $reason !== null,
                'duplicate_reason' => $reason,
            ];
        })->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, string|int>  $selectedKeys
     * @return array{imported:int,skipped_duplicates:int,failed:int}
     */
    public function importSelected(NewsSource $source, array $items, array $selectedKeys): array
    {
        $selected = collect($selectedKeys)->map(fn ($key) => (string) $key)->flip();
        $results = ['imported' => 0, 'skipped_duplicates' => 0, 'failed' => 0];

        foreach (collect($items)->values() as $index => $item) {
            if (! $selected->has((string) $index)) {
                continue;
            }

            $externalId = $this->clean($item['external_id'] ?? null);
            $sourceUrl = $this->clean($item['source_url'] ?? null);
            $importedHash = $this->importedHash($item);

            if ($this->duplicateReason($source, $externalId, $sourceUrl, $importedHash) !== null) {
                $results['skipped_duplicates']++;

                continue;
            }

            try {
                $title = $this->clean($item['title'] ?? null) ?? 'Untitled';

                NewsItem::query()->create([
                    'news_source_id' => $source->id,
                    'news_category_id' => $source->default_news_category_id,
                    'location_id' => $source->default_location_id,
                    'title' => $title,
                    'slug' => $this->generateUniqueSlug($title),
                    'summary' => $this->clean($item['summary'] ?? null),
                    'body' => $this->clean($item['body'] ?? null) ?? $this->clean($item['summary'] ?? null),
                    'source_url' => $sourceUrl,
                    'author' => $this->clean($item['author'] ?? null),
                    'language' => $this->resolveLanguage($source),
                    'published_at' => $this->parseDate($item['published_at'] ?? null),
                    'collected_at' => now(),
                    'status' => 'collected',
                    'editorial_priority' => 3,
                    'is_evergreen' => false,
                    'external_id' => $externalId,
                    'imported_hash' => $importedHash,
                    'metadata' => [
                        'imported_from' => 'rss',
                        'source_id' => $source->id,
                        'raw' => is_array($item['metadata'] ?? null) ? $item['metadata'] : [],
                    ],
                ]);

                $results['imported']++;
            } catch (\Throwable) {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function importedHash(array $item): string
    {
        $externalId = $this->clean($item['external_id'] ?? null);
        $sourceUrl = $this->clean($item['source_url'] ?? null);
        $title = $this->clean($item['title'] ?? null);
        $publishedAt = $this->parseDate($item['published_at'] ?? null)?->toIso8601String();

        return hash('sha256', implode('|', [
            $externalId ?? '',
            $sourceUrl ?? '',
            $title ?? '',
            $publishedAt ?? '',
        ]));
    }

    private function duplicateReason(NewsSource $source, ?string $externalId, ?string $sourceUrl, string $importedHash): ?string
    {
        if ($externalId && NewsItem::query()->where('news_source_id', $source->id)->where('external_id', $externalId)->exists()) {
            return 'external_id';
        }

        if ($sourceUrl && NewsItem::query()->where('news_source_id', $source->id)->where('source_url', $sourceUrl)->exists()) {
            return 'source_url';
        }

        if (NewsItem::query()->where('news_source_id', $source->id)->where('imported_hash', $importedHash)->exists()) {
            return 'imported_hash';
        }

        return null;
    }

    private function clean(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if (! is_string($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveLanguage(NewsSource $source): string
    {
        $location = $source->default_location_id
            ? Location::query()->with('defaultLanguage')->find($source->default_location_id)
            : null;

        return $this->editorialLanguage->resolveCode($location, $source->language);
    }

    private function generateUniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $base = $base !== '' ? $base : 'news-item';
        $slug = $base;
        $counter = 1;

        while (NewsItem::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
