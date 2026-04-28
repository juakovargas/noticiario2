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
            'title' => ['TITLE', 'TÍTULO', 'TITULO'],
            'intro' => ['INTRO', 'INTRODUCTION', 'INTRODUCCIÓN', 'INTRODUCCION', 'ENTRADILLA'],
            'news_items' => ['NEWS ITEMS', 'ITEMS', 'NOTICIAS'],
            'outro' => ['OUTRO', 'CIERRE'],
            'notes' => ['NOTES', 'NOTAS'],
        ];

        $sections = $this->extractSections($raw, $sectionMap);
        $items = $this->parseItems($sections['news_items'] ?? '');

        $hasStructuredSections = collect(['title', 'intro', 'news_items', 'outro', 'notes'])
            ->some(fn (string $key): bool => ($sections[$key] ?? null) !== null);

        $warnings = $this->buildWarnings($sections, $items, $hasStructuredSections);

        if (! $hasStructuredSections && count($items) === 0) {
            return [
                'title' => null,
                'intro' => null,
                'body' => $raw,
                'outro' => null,
                'notes' => null,
                'items' => [],
                'warnings' => ['unstructured_response', 'no_script_blocks'],
                'raw' => $raw,
            ];
        }

        return [
            'title' => $this->nullIfEmpty($sections['title'] ?? null),
            'intro' => $this->nullIfEmpty($sections['intro'] ?? null),
            'body' => $this->buildBody($items, $raw),
            'outro' => $this->nullIfEmpty($sections['outro'] ?? null),
            'notes' => $this->nullIfEmpty($sections['notes'] ?? null),
            'items' => $items,
            'warnings' => $warnings,
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

        $sourcePattern = '(?:HEADLINE|TITULAR)\s*:';
        $matches = preg_match_all('/(?:(?<=\n)|^)\s*(?:\d+\.|\d+\)|[-*•])?\s*'.$sourcePattern.'/iu', $text, $all, PREG_OFFSET_CAPTURE);

        if ($matches === false || $matches === 0) {
            return [];
        }

        $offsets = array_map(static fn (array $hit): int => (int) $hit[1], $all[0]);
        $offsets[] = strlen($text);

        $items = [];

        for ($i = 0; $i < count($offsets) - 1; $i++) {
            $start = $offsets[$i];
            $end = $offsets[$i + 1];
            $block = trim(substr($text, $start, $end - $start));

            if ($block === '') {
                continue;
            }

            $headline = $this->matchSection($block, ['HEADLINE', 'TITULAR'], ['SUMMARY', 'RESUMEN', 'SCRIPT', 'GUION', 'GUIÓN', 'EDITORIAL ANGLE', 'ENFOQUE EDITORIAL', 'SOURCE HINTS', 'PISTAS DE FUENTES', 'FUENTES']);
            $summary = $this->matchSection($block, ['SUMMARY', 'RESUMEN'], ['SCRIPT', 'GUION', 'GUIÓN', 'EDITORIAL ANGLE', 'ENFOQUE EDITORIAL', 'SOURCE HINTS', 'PISTAS DE FUENTES', 'FUENTES']);
            $script = $this->matchSection($block, ['SCRIPT', 'GUION', 'GUIÓN'], ['EDITORIAL ANGLE', 'ENFOQUE EDITORIAL', 'SOURCE HINTS', 'PISTAS DE FUENTES', 'FUENTES']);
            $editorialAngle = $this->matchSection($block, ['EDITORIAL ANGLE', 'ENFOQUE EDITORIAL'], ['SOURCE HINTS', 'PISTAS DE FUENTES', 'FUENTES']);
            $sourceHintsText = $this->matchSection($block, ['SOURCE HINTS', 'PISTAS DE FUENTES', 'FUENTES'], []);
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
                'raw' => $block,
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
            ? '/(?:^|\n)\s*(?:\d+\.|\d+\)|[-*•]\s*)?'.$labelRegex.'\s*:\s*(.*?)\s*(?=\n\s*(?:\d+\.|\d+\)|[-*•]\s*)?(?:'.$nextRegex.')\s*:|$)/isu'
            : '/(?:^|\n)\s*(?:\d+\.|\d+\)|[-*•]\s*)?'.$labelRegex.'\s*:\s*(.*)$/isu';

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
    private function buildBody(array $items, string $raw): string
    {
        if (count($items) === 0) {
            return $raw;
        }

        $blocks = [];

        foreach ($items as $item) {
            $headline = trim((string) ($item['headline'] ?? ''));
            $script = trim((string) ($item['script'] ?? ''));

            if ($script === '') {
                continue;
            }

            $blocks[] = trim(($headline !== '' ? '['.$headline."]\n" : '').$script);
        }

        return count($blocks) > 0 ? implode("\n\n", $blocks) : $raw;
    }

    /**
     * @param  array<string, string|null>  $sections
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, string>
     */
    private function buildWarnings(array $sections, array $items, bool $hasStructuredSections): array
    {
        $warnings = [];

        if (! $hasStructuredSections) {
            $warnings[] = 'unstructured_response';
        }

        if ($this->nullIfEmpty($sections['title'] ?? null) === null) {
            $warnings[] = 'missing_title';
        }

        if ($this->nullIfEmpty($sections['intro'] ?? null) === null) {
            $warnings[] = 'missing_intro';
        }

        $scriptBlocks = collect($items)->filter(fn (array $item): bool => $this->nullIfEmpty((string) ($item['script'] ?? '')) !== null)->count();
        if ($scriptBlocks === 0) {
            $warnings[] = 'no_script_blocks';
        }

        $missingSources = collect($items)->contains(fn (array $item): bool => count($item['source_hints'] ?? []) === 0);
        if (count($items) > 0 && $missingSources) {
            $warnings[] = 'missing_sources';
        }

        return array_values(array_unique($warnings));
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
            'warnings' => ['unstructured_response', 'missing_title', 'missing_intro', 'no_script_blocks'],
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
