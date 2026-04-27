<?php

namespace App\Support;

use App\Models\Edition;
use App\Models\EditorialTemplate;
use App\Models\NewsItem;
use Illuminate\Support\Collection;

class EditorialTemplateRenderer
{
    /**
     * @param  Collection<int, NewsItem>  $newsItems
     * @return array{intro: string, body: string, outro: string}
     */
    public function render(EditorialTemplate $template, Edition $edition, Collection $newsItems): array
    {
        $newsBlock = $newsItems
            ->values()
            ->map(function (NewsItem $item, int $index): string {
                $parts = [
                    ($index + 1).'. '.$item->title,
                    $item->pivot?->editorial_angle ? 'Angle: '.trim((string) $item->pivot->editorial_angle) : null,
                    $item->summary ? 'Summary: '.trim($item->summary) : null,
                ];

                return implode("\n", array_values(array_filter($parts)));
            })
            ->implode("\n\n");

        $replacements = [
            '{{edition_title}}' => $edition->title,
            '{{edition_type}}' => (string) $edition->edition_type,
            '{{location_name}}' => (string) ($edition->location?->name ?? ''),
            '{{language_code}}' => (string) ($edition->language ?? ''),
            '{{news_items}}' => $newsBlock,
            '{{date}}' => now()->toDateString(),
        ];

        return [
            'intro' => strtr((string) ($template->intro_template ?? ''), $replacements),
            'body' => strtr((string) ($template->body_template ?? ''), $replacements),
            'outro' => strtr((string) ($template->outro_template ?? ''), $replacements),
        ];
    }
}
