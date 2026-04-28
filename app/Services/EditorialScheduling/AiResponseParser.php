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
            return $this->baseResult($raw, ['unstructured_response']);
        }

        $sections = $this->extractTopLevelSections($raw);
        $items = $this->parseItems((string) ($sections['news_items'] ?? ''));

        $title = $this->nullIfEmpty($sections['title'] ?? null);
        $intro = $this->nullIfEmpty($sections['intro'] ?? null);
        $outro = $this->nullIfEmpty($sections['outro'] ?? null);
        $notes = $this->nullIfEmpty($sections['notes'] ?? null);

        $hasStructuredSections = $title !== null
            || $intro !== null
            || $this->nullIfEmpty($sections['news_items'] ?? null) !== null
            || $outro !== null
            || $notes !== null;

        if (! $hasStructuredSections && count($items) === 0) {
            return $this->baseResult($raw, ['unstructured_response']);
        }

        $warnings = $this->buildWarnings($title, $intro, $items, $hasStructuredSections);

        return [
            'title' => $title,
            'intro' => $intro,
            'items' => $items,
            'outro' => $outro,
            'notes' => $notes,
            'warnings' => $warnings,
            'raw' => $raw,
            'body' => $this->buildBody($items, $raw),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function extractTopLevelSections(string $text): array
    {
        $sections = [
            'title' => null,
            'intro' => null,
            'news_items' => null,
            'outro' => null,
            'notes' => null,
        ];

        $lines = preg_split('/\n/u', $text) ?: [];
        $current = null;
        $buffers = [
            'title' => [],
            'intro' => [],
            'news_items' => [],
            'outro' => [],
            'notes' => [],
        ];

        foreach ($lines as $line) {
            $label = $this->resolveTopLevelLabel($line);

            if ($label !== null) {
                $current = $label;
                $inlineContent = $this->extractInlineContent($line);
                if ($inlineContent !== null && $inlineContent !== '') {
                    $buffers[$current][] = $inlineContent;
                }
                continue;
            }

            if ($current !== null) {
                $buffers[$current][] = $line;
            }
        }

        foreach (array_keys($sections) as $key) {
            $content = trim(implode("\n", $buffers[$key]));
            $sections[$key] = $content !== '' ? $content : null;
        }

        return $sections;
    }

    private function resolveTopLevelLabel(string $line): ?string
    {
        $normalized = $this->normalizeLabel($line);

        return match ($normalized) {
            'TITLE', 'TITULO', 'TÍTULO' => 'title',
            'INTRO', 'INTRODUCTION', 'INTRODUCCION', 'INTRODUCCIÓN', 'ENTRADILLA' => 'intro',
            'NEWS ITEMS', 'ITEMS', 'NOTICIAS' => 'news_items',
            'OUTRO', 'CIERRE' => 'outro',
            'NOTES', 'NOTAS' => 'notes',
            default => null,
        };
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

        preg_match_all('/(?:(?<=\n)|^)\s*(?:\d+[.)]\s*)?(?:HEADLINE|TITULAR)\s*:/iu', $text, $matches, PREG_OFFSET_CAPTURE);

        if (empty($matches[0])) {
            return [];
        }

        $offsets = array_map(static fn (array $hit): int => (int) $hit[1], $matches[0]);
        $offsets[] = strlen($text);

        $items = [];

        for ($index = 0; $index < count($offsets) - 1; $index++) {
            $start = $offsets[$index];
            $end = $offsets[$index + 1];
            $block = trim(substr($text, $start, $end - $start));

            if ($block === '') {
                continue;
            }

            $headline = $this->extractField($block, ['HEADLINE', 'TITULAR'], ['SUMMARY', 'RESUMEN', 'SCRIPT', 'GUION', 'GUIÓN', 'EDITORIAL ANGLE', 'ENFOQUE EDITORIAL', 'SOURCE HINTS', 'PISTAS DE FUENTES', 'FUENTES']);
            $summary = $this->extractField($block, ['SUMMARY', 'RESUMEN'], ['SCRIPT', 'GUION', 'GUIÓN', 'EDITORIAL ANGLE', 'ENFOQUE EDITORIAL', 'SOURCE HINTS', 'PISTAS DE FUENTES', 'FUENTES']);
            $script = $this->extractField($block, ['SCRIPT', 'GUION', 'GUIÓN'], ['EDITORIAL ANGLE', 'ENFOQUE EDITORIAL', 'SOURCE HINTS', 'PISTAS DE FUENTES', 'FUENTES']);
            $editorialAngle = $this->extractField($block, ['EDITORIAL ANGLE', 'ENFOQUE EDITORIAL'], ['SOURCE HINTS', 'PISTAS DE FUENTES', 'FUENTES']);
            $sourceHintsText = $this->extractField($block, ['SOURCE HINTS', 'PISTAS DE FUENTES', 'FUENTES'], []);
            $sourceHints = $this->parseSourceHints($sourceHintsText);

            if ($headline === null && $summary === null && $script === null && $editorialAngle === null && $sourceHints === []) {
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
    private function extractField(string $block, array $labels, array $nextLabels): ?string
    {
        $labelPattern = implode('|', array_map(fn (string $label): string => preg_quote($label, '/'), $labels));
        $nextPattern = count($nextLabels) > 0
            ? implode('|', array_map(fn (string $label): string => preg_quote($label, '/'), $nextLabels))
            : null;

        $pattern = $nextPattern !== null
            ? '/(?:^|\n)\s*(?:\d+[.)]\s*)?(?:'.$labelPattern.')\s*:\s*(.*?)\s*(?=\n\s*(?:\d+[.)]\s*)?(?:'.$nextPattern.')\s*:|$)/isu'
            : '/(?:^|\n)\s*(?:\d+[.)]\s*)?(?:'.$labelPattern.')\s*:\s*(.*?)\s*$/isu';

        if (preg_match($pattern, $block, $match) !== 1) {
            return null;
        }

        return $this->nullIfEmpty((string) ($match[1] ?? ''));
    }

    /**
     * @return array<int, string>
     */
    private function parseSourceHints(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        $lines = preg_split('/\n+/u', trim($value)) ?: [];

        return collect($lines)
            ->map(static fn (string $line): string => trim((string) preg_replace('/^[-*•\d.)\s]+/u', '', $line)))
            ->filter(static fn (string $line): bool => $line !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, string>
     */
    private function buildWarnings(?string $title, ?string $intro, array $items, bool $hasStructuredSections): array
    {
        $warnings = [];

        if (! $hasStructuredSections) {
            $warnings[] = 'unstructured_response';
        }

        if ($title === null) {
            $warnings[] = 'missing_title';
        }

        if ($intro === null) {
            $warnings[] = 'missing_intro';
        }

        $scriptCount = collect($items)
            ->filter(fn (array $item): bool => $this->nullIfEmpty((string) ($item['script'] ?? null)) !== null)
            ->count();

        if (count($items) > 0 && $scriptCount === 0) {
            $warnings[] = 'no_script_blocks';
        }

        $sourceHintCount = collect($items)
            ->flatMap(fn (array $item): array => is_array($item['source_hints'] ?? null) ? $item['source_hints'] : [])
            ->count();

        if (count($items) > 0 && $sourceHintCount === 0) {
            $warnings[] = 'missing_sources';
        }

        return array_values(array_unique($warnings));
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function buildBody(array $items, string $raw): string
    {
        $blocks = [];

        foreach ($items as $item) {
            $script = trim((string) ($item['script'] ?? ''));

            if ($script === '') {
                continue;
            }

            $headline = trim((string) ($item['headline'] ?? ''));
            $blocks[] = trim(($headline !== '' ? '['.$headline."]\n" : '').$script);
        }

        return count($blocks) > 0 ? implode("\n\n", $blocks) : $raw;
    }

    /**
     * @param  array<int, string>  $warnings
     * @return array<string, mixed>
     */
    private function baseResult(string $raw, array $warnings): array
    {
        return [
            'title' => null,
            'intro' => null,
            'items' => [],
            'outro' => null,
            'notes' => null,
            'warnings' => $warnings,
            'raw' => $raw,
            'body' => $raw,
        ];
    }

    private function extractInlineContent(string $line): ?string
    {
        if (preg_match('/^[^:]+:\s*(.*)$/u', trim($line), $matches) !== 1) {
            return null;
        }

        return trim((string) ($matches[1] ?? ''));
    }

    private function normalizeLabel(string $line): string
    {
        $beforeColon = explode(':', trim($line), 2)[0] ?? '';
        $withoutIndex = (string) preg_replace('/^\s*\d+[.)]\s*/u', '', $beforeColon);

        return mb_strtoupper(trim($withoutIndex));
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
