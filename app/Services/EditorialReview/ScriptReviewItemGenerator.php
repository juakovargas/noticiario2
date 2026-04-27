<?php

namespace App\Services\EditorialReview;

use App\Models\NewsItem;
use App\Models\Script;
use App\Models\ScriptReviewItem;
use Illuminate\Support\Collection;

class ScriptReviewItemGenerator
{
    /**
     * @return Collection<int, ScriptReviewItem>
     */
    public function generateForScript(Script $script): Collection
    {
        if ($script->reviewItems()->exists()) {
            return $script->reviewItems()->get();
        }

        return $this->createItems($script);
    }

    /**
     * @return Collection<int, ScriptReviewItem>
     */
    public function regenerateForScript(Script $script, bool $force = false): Collection
    {
        if (! $force && $script->reviewItems()->exists()) {
            return $script->reviewItems()->get();
        }

        if ($force) {
            $script->reviewItems()->delete();
        }

        return $this->createItems($script);
    }

    /**
     * @return Collection<int, ScriptReviewItem>
     */
    private function createItems(Script $script): Collection
    {
        $script->loadMissing(['edition.newsItems']);

        $items = collect();
        $sortOrder = 1;

        if ($script->intro) {
            $items->push($this->buildItem($script, [
                'sort_order' => $sortOrder++,
                'type' => 'intro',
                'title' => 'Intro',
                'content' => $script->intro,
                'required_action' => 'none',
            ]));
        }

        $parsedItems = $this->parsedItemsFromMetadata($script);

        if ($parsedItems->isNotEmpty()) {
            foreach ($parsedItems as $index => $parsed) {
                $headline = trim((string) ($parsed['headline'] ?? $parsed['title'] ?? ''));
                $scriptText = trim((string) ($parsed['script'] ?? $parsed['content'] ?? $parsed['summary'] ?? ''));
                $sourceHints = collect($parsed['source_hints'] ?? [])->map(fn ($hint) => trim((string) $hint))->filter()->values()->all();

                $items->push($this->buildItem($script, [
                    'sort_order' => $sortOrder++,
                    'type' => 'news_item',
                    'title' => $headline !== '' ? $headline : 'News Item '.($index + 1),
                    'content' => $scriptText !== '' ? $scriptText : null,
                    'source_hints' => $sourceHints,
                    'required_action' => 'none',
                    'metadata' => [
                        'editorial_angle' => $parsed['editorial_angle'] ?? null,
                        'summary' => $parsed['summary'] ?? null,
                        'raw' => $parsed['raw'] ?? null,
                    ],
                ]));
            }
        } else {
            $body = trim((string) $script->body);
            $chunks = $body !== ''
                ? collect(preg_split('/\n\s*\n/u', $body) ?: [])->map(fn (string $chunk) => trim($chunk))->filter()
                : collect();

            if ($chunks->isEmpty() && $body !== '') {
                $chunks = collect([$body]);
            }

            if ($chunks->isEmpty()) {
                $chunks = collect(['']);
            }

            foreach ($chunks as $index => $chunk) {
                $items->push($this->buildItem($script, [
                    'sort_order' => $sortOrder++,
                    'type' => 'script_block',
                    'title' => 'Body Block '.($index + 1),
                    'content' => $chunk !== '' ? $chunk : null,
                    'required_action' => 'none',
                ]));
            }
        }

        if ($script->outro) {
            $items->push($this->buildItem($script, [
                'sort_order' => $sortOrder++,
                'type' => 'outro',
                'title' => 'Outro',
                'content' => $script->outro,
                'required_action' => 'none',
            ]));
        }

        if ($script->edition) {
            /** @var Collection<int, NewsItem> $editionNewsItems */
            $editionNewsItems = $script->edition->newsItems;

            foreach ($editionNewsItems as $newsItem) {
                $items->push($this->buildItem($script, [
                    'sort_order' => $sortOrder++,
                    'type' => 'news_item',
                    'news_item_id' => $newsItem->id,
                    'title' => $newsItem->title,
                    'content' => $newsItem->summary,
                    'required_action' => 'none',
                    'metadata' => [
                        'edition_linked' => true,
                    ],
                ]));
            }
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function buildItem(Script $script, array $attributes): ScriptReviewItem
    {
        return ScriptReviewItem::query()->create(array_merge([
            'script_id' => $script->id,
            'verification_status' => 'pending',
        ], $attributes));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function parsedItemsFromMetadata(Script $script): Collection
    {
        $metadata = is_array($script->metadata) ? $script->metadata : [];

        if (is_array($metadata['parsed_response']['items'] ?? null)) {
            return collect($metadata['parsed_response']['items'])->filter(fn ($item) => is_array($item))->values();
        }

        if (is_array($metadata['parsed_items'] ?? null)) {
            return collect($metadata['parsed_items'])->filter(fn ($item) => is_array($item))->values();
        }

        return collect();
    }
}
