<?php

namespace App\Services\NewsIngestion;

use Illuminate\Support\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class RssFeedReader
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function preview(string $feedUrl, int $limit = 30): array
    {
        $response = Http::timeout(10)->accept('application/rss+xml, application/atom+xml, application/xml, text/xml, */*')->get($feedUrl);

        if (! $response->successful()) {
            return [];
        }

        return $this->parseString((string) $response->body(), $limit);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function parseString(string $feedXml, int $limit = 30): array
    {
        if (trim($feedXml) === '') {
            return [];
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($feedXml, 'SimpleXMLElement', LIBXML_NOCDATA);

        if ($xml === false) {
            return [];
        }

        if (isset($xml->channel)) {
            return $this->parseRssItems($xml, $limit);
        }

        if ($xml->getName() === 'feed') {
            return $this->parseAtomItems($xml, $limit);
        }

        return [];
    }

    private function parseRssItems(\SimpleXMLElement $xml, int $limit): array
    {
        $items = [];

        foreach ($xml->channel->item ?? [] as $item) {
            if (count($items) >= $limit || ! $item instanceof \SimpleXMLElement) {
                break;
            }

            $metadata = [
                'guid' => $this->nullableString((string) ($item->guid ?? '')),
                'categories' => array_values(array_filter(array_map(fn ($category) => $this->nullableString((string) $category), iterator_to_array($item->category ?? [])))),
            ];

            $items[] = [
                'external_id' => $this->nullableString((string) ($item->guid ?? '')),
                'title' => $this->nullableString((string) ($item->title ?? '')) ?? 'Untitled',
                'summary' => $this->nullableString((string) ($item->description ?? '')),
                'body' => $this->nullableString((string) ($item->children('content', true)->encoded ?? '')),
                'source_url' => $this->nullableString((string) ($item->link ?? '')),
                'author' => $this->nullableString((string) ($item->author ?? $item->children('dc', true)->creator ?? '')),
                'published_at' => $this->parseDate($this->nullableString((string) ($item->pubDate ?? ''))),
                'metadata' => Arr::whereNotNull($metadata),
            ];
        }

        return $items;
    }

    private function parseAtomItems(\SimpleXMLElement $xml, int $limit): array
    {
        $items = [];

        foreach ($xml->entry ?? [] as $entry) {
            if (count($items) >= $limit || ! $entry instanceof \SimpleXMLElement) {
                break;
            }

            $link = null;
            foreach ($entry->link ?? [] as $atomLink) {
                $attrs = $atomLink->attributes();
                $href = $this->nullableString((string) ($attrs['href'] ?? ''));
                if (! $href) {
                    continue;
                }

                $rel = $this->nullableString((string) ($attrs['rel'] ?? 'alternate'));
                if ($rel === 'alternate' || $rel === null) {
                    $link = $href;
                    break;
                }

                $link ??= $href;
            }

            $summary = $this->nullableString((string) ($entry->summary ?? ''));
            $content = $this->nullableString((string) ($entry->content ?? ''));
            $published = $this->nullableString((string) ($entry->published ?? $entry->updated ?? ''));

            $metadata = [
                'id' => $this->nullableString((string) ($entry->id ?? '')),
                'updated' => $this->nullableString((string) ($entry->updated ?? '')),
            ];

            $items[] = [
                'external_id' => $this->nullableString((string) ($entry->id ?? '')),
                'title' => $this->nullableString((string) ($entry->title ?? '')) ?? 'Untitled',
                'summary' => $summary,
                'body' => $content,
                'source_url' => $link,
                'author' => $this->nullableString((string) ($entry->author->name ?? '')),
                'published_at' => $this->parseDate($published),
                'metadata' => Arr::whereNotNull($metadata),
            ];
        }

        return $items;
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function nullableString(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return $value === '' ? null : $value;
    }
}
