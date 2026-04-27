<?php

namespace Tests\Unit;

use App\Models\Language;
use App\Models\Location;
use App\Models\NewsCategory;
use App\Models\NewsItem;
use App\Models\NewsSource;
use App\Services\NewsIngestion\NewsItemImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsItemImporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_item_and_applies_source_defaults_and_language(): void
    {
        $language = Language::factory()->create(['code' => 'es', 'is_default' => true, 'is_active' => true]);
        $location = Location::factory()->create(['default_language_id' => $language->id]);
        $category = NewsCategory::factory()->create();
        $source = NewsSource::factory()->create([
            'type' => 'rss',
            'feed_url' => 'https://example.com/rss.xml',
            'default_news_category_id' => $category->id,
            'default_location_id' => $location->id,
            'language' => null,
        ]);

        $items = [[
            'external_id' => 'guid-1',
            'title' => 'Imported title',
            'summary' => 'Imported summary',
            'body' => null,
            'source_url' => 'https://example.com/news/1',
            'author' => 'Desk',
            'published_at' => '2026-04-27 10:00:00',
            'metadata' => ['guid' => 'guid-1'],
        ]];

        $result = app(NewsItemImporter::class)->importSelected($source, $items, ['0']);

        $this->assertSame(1, $result['imported']);
        $this->assertDatabaseHas('news_items', [
            'news_source_id' => $source->id,
            'news_category_id' => $category->id,
            'location_id' => $location->id,
            'language' => 'es',
            'external_id' => 'guid-1',
            'status' => 'collected',
        ]);
    }

    public function test_it_skips_duplicate_by_external_id(): void
    {
        $source = NewsSource::factory()->create();
        NewsItem::factory()->create(['news_source_id' => $source->id, 'external_id' => 'dup-guid']);

        $items = [[
            'external_id' => 'dup-guid',
            'title' => 'Duplicate item',
            'source_url' => 'https://example.com/a',
            'published_at' => '2026-04-27 10:00:00',
        ]];

        $result = app(NewsItemImporter::class)->importSelected($source, $items, ['0']);

        $this->assertSame(0, $result['imported']);
        $this->assertSame(1, $result['skipped_duplicates']);
    }

    public function test_it_skips_duplicate_by_source_url_and_imported_hash(): void
    {
        $source = NewsSource::factory()->create();

        NewsItem::factory()->create([
            'news_source_id' => $source->id,
            'source_url' => 'https://example.com/dup-url',
            'external_id' => null,
        ]);

        $itemsByUrl = [[
            'external_id' => null,
            'title' => 'Duplicate by url',
            'source_url' => 'https://example.com/dup-url',
            'published_at' => '2026-04-27 10:00:00',
        ]];

        $resultByUrl = app(NewsItemImporter::class)->importSelected($source, $itemsByUrl, ['0']);
        $this->assertSame(1, $resultByUrl['skipped_duplicates']);

        $item = [
            'external_id' => null,
            'title' => 'Duplicate by hash',
            'source_url' => null,
            'published_at' => '2026-04-27 10:00:00',
        ];
        $hash = app(NewsItemImporter::class)->importedHash($item);

        NewsItem::factory()->create([
            'news_source_id' => $source->id,
            'source_url' => null,
            'external_id' => null,
            'imported_hash' => $hash,
        ]);

        $resultByHash = app(NewsItemImporter::class)->importSelected($source, [$item], ['0']);
        $this->assertSame(1, $resultByHash['skipped_duplicates']);
    }
}
