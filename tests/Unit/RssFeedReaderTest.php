<?php

namespace Tests\Unit;

use App\Services\NewsIngestion\RssFeedReader;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RssFeedReaderTest extends TestCase
{
    public function test_it_parses_valid_rss_feed_xml_string(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>Demo Feed</title>
    <item>
      <title>First item</title>
      <description>Summary one</description>
      <link>https://example.com/news/first</link>
      <guid>guid-1</guid>
      <author>Desk One</author>
      <pubDate>Mon, 27 Apr 2026 10:00:00 +0000</pubDate>
    </item>
    <item>
      <title>Second item</title>
      <description>Summary two</description>
      <link>https://example.com/news/second</link>
      <guid>guid-2</guid>
      <pubDate>Mon, 27 Apr 2026 11:00:00 +0000</pubDate>
    </item>
  </channel>
</rss>
XML;

        $items = app(RssFeedReader::class)->parseString($xml, 30);

        $this->assertCount(2, $items);
        $this->assertSame('First item', $items[0]['title']);
    }

    public function test_it_extracts_main_rss_fields(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <item>
      <title>Detailed item</title>
      <description>Detailed summary</description>
      <link>https://example.com/news/detailed</link>
      <guid>external-123</guid>
      <author>Reporter</author>
      <pubDate>Mon, 27 Apr 2026 12:00:00 +0000</pubDate>
    </item>
  </channel>
</rss>
XML;

        $item = app(RssFeedReader::class)->parseString($xml, 5)[0];

        $this->assertSame('Detailed item', $item['title']);
        $this->assertSame('Detailed summary', $item['summary']);
        $this->assertSame('https://example.com/news/detailed', $item['source_url']);
        $this->assertSame('Reporter', $item['author']);
        $this->assertSame('external-123', $item['external_id']);
        $this->assertInstanceOf(Carbon::class, $item['published_at']);
    }

    public function test_it_handles_invalid_or_empty_feed_gracefully(): void
    {
        $reader = app(RssFeedReader::class);

        $this->assertSame([], $reader->parseString('', 30));
        $this->assertSame([], $reader->parseString('<not-valid', 30));
    }
}
