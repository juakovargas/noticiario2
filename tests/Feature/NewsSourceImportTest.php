<?php

namespace Tests\Feature;

use App\Models\NewsSource;
use App\Services\NewsIngestion\RssFeedReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class NewsSourceImportTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    public function test_editor_can_access_source_import_preview_for_active_rss_source(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);
        $source = NewsSource::factory()->create(['type' => 'rss', 'feed_url' => 'https://example.com/rss.xml', 'is_active' => true]);

        $this->mock(RssFeedReader::class, function ($mock): void {
            $mock->shouldReceive('preview')->once()->andReturn([[ 'title' => 'Feed item', 'summary' => null, 'body' => null, 'source_url' => 'https://example.com/a', 'author' => null, 'external_id' => 'a1', 'published_at' => now(), 'metadata' => [] ]]);
        });

        $this->actingAs($editor)->get(route('editor.news-sources.import', $source))->assertOk();
    }

    public function test_viewer_or_non_editor_cannot_access_import_preview(): void
    {
        $viewer = $this->createUserWithPermissions(['viewer.access']);
        $noEditor = $this->createUserWithPermissions([]);
        $source = NewsSource::factory()->create(['type' => 'rss', 'feed_url' => 'https://example.com/rss.xml', 'is_active' => true]);

        $this->actingAs($viewer)->get(route('editor.news-sources.import', $source))->assertForbidden();
        $this->actingAs($noEditor)->get(route('editor.news-sources.import', $source))->assertForbidden();
    }

    public function test_editor_can_import_selected_items_and_skips_duplicates(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);
        $source = NewsSource::factory()->create(['type' => 'rss', 'feed_url' => 'https://example.com/rss.xml', 'is_active' => true]);

        $items = [
            ['external_id' => 'a1', 'title' => 'Item A', 'summary' => 'S', 'body' => null, 'source_url' => 'https://example.com/a', 'author' => 'Desk', 'published_at' => now(), 'metadata' => []],
            ['external_id' => 'a1', 'title' => 'Item A dup', 'summary' => 'S', 'body' => null, 'source_url' => 'https://example.com/a2', 'author' => 'Desk', 'published_at' => now(), 'metadata' => []],
        ];

        $this->mock(RssFeedReader::class, function ($mock) use ($items): void {
            $mock->shouldReceive('preview')->once()->andReturn($items);
        });

        $response = $this->actingAs($editor)->post(route('editor.news-sources.import.store', $source), [
            'selected_keys' => ['0', '1'],
        ]);

        $response->assertRedirect(route('editor.news-items.index'));
        $this->assertDatabaseCount('news_items', 1);
    }

    public function test_invalid_sources_cannot_be_imported(): void
    {
        $editor = $this->createUserWithPermissions(['editor.access']);
        $inactive = NewsSource::factory()->create(['type' => 'rss', 'feed_url' => 'https://example.com/rss.xml', 'is_active' => false]);
        $nonRss = NewsSource::factory()->create(['type' => 'manual', 'feed_url' => null, 'is_active' => true]);
        $withoutFeed = NewsSource::factory()->create(['type' => 'rss', 'feed_url' => null, 'is_active' => true]);

        $this->actingAs($editor)->get(route('editor.news-sources.import', $inactive))->assertRedirect(route('editor.news-sources.index'));
        $this->actingAs($editor)->get(route('editor.news-sources.import', $nonRss))->assertRedirect(route('editor.news-sources.index'));
        $this->actingAs($editor)->get(route('editor.news-sources.import', $withoutFeed))->assertRedirect(route('editor.news-sources.index'));
    }
}
