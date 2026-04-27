<?php

namespace App\Services\EditorialScheduling;

class AiResponseParser
{
    /**
     * @return array<string, mixed>
     */
    public function parse(string $responseText): array
    {
        $raw = trim(str_replace(["\r\n", "\r"], "\n", $responseText));

        if ($raw === '') {
            return $this->emptyResult($raw);
        }

        $sectionMap = [
            'title' => ['TITLE', 'TÍTULO'],
            'intro' => ['INTRO', 'INTRODUCTION', 'ENTRADILLA'],
            'news_items' => ['NEWS ITEMS', 'ITEMS', 'NOTICIAS'],
            'outro' => ['OUTRO', 'CIERRE'],
            'notes' => ['NOTES', 'NOTAS'],
        ];

        $sections = $this->extractSections($raw, $sectionMap);
        $items = $this->parseItems($sections['news_items'] ?? '');

        $hasStructuredSections = collect(['title', 'intro', 'news_items', 'outro', 'notes'])
            ->some(fn (string $key): bool => ($sections[$key] ?? null) !== null);

        if (! $hasStructuredSections && count($items) === 0) {
            return [
                'title' => null,
                'intro' => null,
                'body' => $raw,
                'outro' => null,
                'notes' => null,
                'items' => [],
                'raw' => $raw,
            ];
        }

        $body = $this->buildBody($items, $sections['news_items'] ?? null, $raw);

        return [
            'title' => $this->nullIfEmpty($sections['title'] ?? null),
            'intro' => $this->nullIfEmpty($sections['intro'] ?? null),
            'body' => $this->nullIfEmpty($body),
            'outro' => $this->nullIfEmpty($sections['outro'] ?? null),
            'notes' => $this->nullIfEmpty($sections['notes'] ?? null),
            'items' => $items,
            'raw' => $raw,
        ];
    }

    /**
     * @param  array<string, array<int, string>>  $sectionMap
     * @return array<string, string|null>
     */
    private function extractSections(string $text, array $sectionMap): array
    {
        $patternToKey = [];

        foreach ($sectionMap as $key => $labels) {
            foreach ($labels as $label) {
                $patternToKey[preg_quote($label, '/')] = $key;
            }
        }

        $labelRegex = '/^\s*(?:'.implode('|', array_keys($patternToKey)).')\s*:\s*$/imu';

        preg_match_all($labelRegex, $text, $matches, PREG_OFFSET_CAPTURE);

        $result = array_fill_keys(array_keys($sectionMap), null);

        if (empty($matches[0])) {
            return $result;
        }

        $hits = [];

        foreach ($matches[0] as [$fullMatch, $offset]) {
            preg_match('/^\s*(.*?)\s*:/u', $fullMatch, $labelMatch);
            $label = mb_strtoupper(trim($labelMatch[1] ?? ''));

            foreach ($patternToKey as $pattern => $key) {
                if (preg_match('/^'.$pattern.'$/iu', $label) === 1) {
                    $hits[] = [
                        'key' => $key,
                        'offset' => $offset,
                        'length' => strlen($fullMatch),
                    ];
                    break;
                }
            }
        }

        $total = count($hits);

        for ($i = 0; $i < $total; $i++) {
            $current = $hits[$i];
            $start = $current['offset'] + $current['length'];
            $end = $i < $total - 1 ? $hits[$i + 1]['offset'] : strlen($text);
            $content = trim(substr($text, $start, $end - $start));

            if ($content !== '') {
                $result[$current['key']] = $content;
            }
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseItems(string $itemsText): array
    {
        $text = trim($itemsText);

        if ($text === '') {
            return [];
        }

        $blocks = preg_split('/\n(?=\s*(?:\d+\.|\d+\)|[-*]\s*)?\s*(?:HEADLINE|TITULAR)\s*:)/iu', $text) ?: [];

        if (count($blocks) <= 1 && preg_match('/\n\s*\d+\s*\.\s*(?:(?!HEADLINE|TITULAR).)*$/imu', $text) === 1) {
            $blocks = preg_split('/\n(?=\s*\d+\s*\.)/u', $text) ?: [];
        }

        $items = [];

        foreach ($blocks as $block) {
            $cleanBlock = trim($block);
            if ($cleanBlock === '') {
                continue;
            }

            $headline = $this->matchSection($cleanBlock, ['HEADLINE', 'TITULAR'], ['SUMMARY', 'RESUMEN', 'SCRIPT', 'GUION', 'EDITORIAL ANGLE', 'ENFOQUE EDITORIAL', 'SOURCE HINTS', 'FUENTES']);
            $summary = $this->matchSection($cleanBlock, ['SUMMARY', 'RESUMEN'], ['SCRIPT', 'GUION', 'EDITORIAL ANGLE', 'ENFOQUE EDITORIAL', 'SOURCE HINTS', 'FUENTES']);
            $script = $this->matchSection($cleanBlock, ['SCRIPT', 'GUION'], ['EDITORIAL ANGLE', 'ENFOQUE EDITORIAL', 'SOURCE HINTS', 'FUENTES']);
            $editorialAngle = $this->matchSection($cleanBlock, ['EDITORIAL ANGLE', 'ENFOQUE EDITORIAL'], ['SOURCE HINTS', 'FUENTES']);
            $sourceHintsText = $this->matchSection($cleanBlock, ['SOURCE HINTS', 'FUENTES'], []);
            $sourceHints = $this->parseSourceHints($sourceHintsText);

            if ($headline === null && $summary === null && $script === null && $editorialAngle === null && count($sourceHints) === 0) {
                continue;
            }

            $items[] = [
                'headline' => $headline,
                'summary' => $summary,
                'script' => $script,
                'editorial_angle' => $editorialAngle,
                'source_hints' => $sourceHints,
                'raw' => $cleanBlock,
            ];
        }

        return $items;
    }

    /**
     * @param  array<int, string>  $labels
     * @param  array<int, string>  $nextLabels
     */
    private function matchSection(string $text, array $labels, array $nextLabels): ?string
    {
        $labelRegex = '(?:'.implode('|', array_map(static fn (string $label): string => preg_quote($label, '/'), $labels)).')';
        $nextRegex = count($nextLabels) > 0
            ? '(?:'.implode('|', array_map(static fn (string $label): string => preg_quote($label, '/'), $nextLabels)).')'
            : null;

        $pattern = $nextRegex
            ? '/(?:^|\n)\s*(?:\d+\.|\d+\)|[-*]\s*)?'.$labelRegex.'\s*:\s*(.*?)\s*(?=\n\s*(?:\d+\.|\d+\)|[-*]\s*)?(?:'.$nextRegex.')\s*:|$)/isu'
            : '/(?:^|\n)\s*(?:\d+\.|\d+\)|[-*]\s*)?'.$labelRegex.'\s*:\s*(.*)$/isu';

        if (preg_match($pattern, $text, $match) !== 1) {
            return null;
        }

        return $this->nullIfEmpty(trim((string) ($match[1] ?? '')));
    }

    /**
     * @return array<int, string>
     */
    private function parseSourceHints(?string $text): array
    {
        if ($text === null || trim($text) === '') {
            return [];
        }

        $lines = preg_split('/\n+/u', trim($text)) ?: [];

        return collect($lines)
            ->map(static fn (string $line): string => trim(preg_replace('/^[-*•\d.)\s]+/u', '', $line) ?? $line))
            ->filter(static fn (string $line): bool => $line !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function buildBody(array $items, ?string $newsItemsSection, string $raw): string
    {
        if (count($items) === 0) {
            return $this->nullIfEmpty($newsItemsSection) ?? $raw;
        }

        $blocks = [];

        foreach ($items as $index => $item) {
            $parts = array_filter([
                $item['headline'] ? ($index + 1).'. '.$item['headline'] : null,
                $item['summary'],
                $item['script'],
                $item['editorial_angle'] ? 'Editorial angle: '.$item['editorial_angle'] : null,
            ]);

            if (count($parts) > 0) {
                $blocks[] = implode("\n", $parts);
            }
        }

        return trim(implode("\n\n", $blocks));
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyResult(string $raw): array
    {
        return [
            'title' => null,
            'intro' => null,
            'body' => $raw === '' ? null : $raw,
            'outro' => null,
            'notes' => null,
            'items' => [],
            'raw' => $raw,
        ];
    }

    private function nullIfEmpty(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
