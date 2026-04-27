<?php

namespace App\Services\EditorialReview;

use App\Models\Script;
use App\Models\ScriptReviewItem;
use App\Models\SourceReference;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SourceReferenceExtractor
{
    public function extractFromScript(Script $script): Collection
    {
        $script->loadMissing(['reviewItems']);

        $created = collect();

        $created = $created->merge($this->storeHints($this->scriptHints($script), [
            'script_id' => $script->id,
            'news_item_id' => null,
            'script_review_item_id' => null,
            'editorial_schedule_run_id' => null,
        ]));

        foreach ($script->reviewItems as $item) {
            $created = $created->merge($this->extractFromReviewItem($item));
        }

        return $created->values();
    }

    public function extractFromReviewItem(ScriptReviewItem $item): Collection
    {
        $hints = collect($item->source_hints ?? [])
            ->merge($this->linesFromValue($item->content))
            ->values();

        return $this->storeHints($hints, [
            'script_id' => $item->script_id,
            'news_item_id' => $item->news_item_id,
            'script_review_item_id' => $item->id,
            'editorial_schedule_run_id' => null,
        ]);
    }

    public function extractFromParsedResponse(array $parsedResponse, ?Script $script = null): Collection
    {
        $hints = collect();

        foreach (['source_hints', 'sources', 'fuentes'] as $key) {
            if (is_array($parsedResponse[$key] ?? null)) {
                $hints = $hints->merge($parsedResponse[$key]);
            }
        }

        if (is_array($parsedResponse['items'] ?? null)) {
            foreach ($parsedResponse['items'] as $item) {
                if (! is_array($item)) {
                    continue;
                }

                if (is_array($item['source_hints'] ?? null)) {
                    $hints = $hints->merge($item['source_hints']);
                }

                foreach (['script', 'content', 'summary', 'title', 'headline', 'raw'] as $field) {
                    if (is_string($item[$field] ?? null)) {
                        $hints = $hints->merge($this->linesFromValue($item[$field]));
                    }
                }
            }
        }

        foreach (['raw_response', 'response_text', 'text', 'body'] as $field) {
            if (is_string($parsedResponse[$field] ?? null)) {
                $hints = $hints->merge($this->sourceSectionLines($parsedResponse[$field]));
            }
        }

        return $this->storeHints($hints, [
            'script_id' => $script?->id,
            'news_item_id' => null,
            'script_review_item_id' => null,
            'editorial_schedule_run_id' => null,
        ]);
    }

    private function scriptHints(Script $script): Collection
    {
        $hints = collect();
        $metadata = is_array($script->metadata) ? $script->metadata : [];

        foreach (['source_hints', 'sources', 'fuentes'] as $key) {
            if (is_array($metadata[$key] ?? null)) {
                $hints = $hints->merge($metadata[$key]);
            }
        }

        if (is_array($metadata['parsed_response'] ?? null)) {
            $hints = $hints->merge($this->parsedResponseHints($metadata['parsed_response']));
        }

        foreach ([$script->intro, $script->body, $script->outro] as $text) {
            if (is_string($text)) {
                $hints = $hints->merge($this->linesFromValue($text));
            }
        }

        return $hints;
    }

    private function sourceSectionLines(string $text): Collection
    {
        $lines = preg_split('/\r\n|\r|\n/u', $text) ?: [];
        $collecting = false;
        $hints = collect();

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                if ($collecting) {
                    break;
                }

                continue;
            }

            if (preg_match('/^(SOURCE\s+HINTS|FUENTES|SOURCES)\s*:/iu', $trimmed)) {
                $collecting = true;
                continue;
            }

            if ($collecting) {
                $hints->push($trimmed);
            }
        }

        return $hints;
    }

    private function parsedResponseHints(array $parsedResponse): Collection
    {
        $hints = collect();

        foreach (['source_hints', 'sources', 'fuentes'] as $key) {
            if (is_array($parsedResponse[$key] ?? null)) {
                $hints = $hints->merge($parsedResponse[$key]);
            }
        }

        if (is_array($parsedResponse['items'] ?? null)) {
            foreach ($parsedResponse['items'] as $item) {
                if (is_array($item['source_hints'] ?? null)) {
                    $hints = $hints->merge($item['source_hints']);
                }
            }
        }

        return $hints;
    }

    private function linesFromValue(mixed $value): Collection
    {
        if (! is_string($value)) {
            return collect();
        }

        return collect(preg_split('/\r\n|\r|\n/u', $value) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter(fn (string $line) => $line !== '' && (str_contains($line, 'http://') || str_contains($line, 'https://') || preg_match('/\b(source|sources|fuente|fuentes)\b/i', $line)))
            ->values();
    }

    /**
     * @param  array<string, int|null>  $context
     * @return Collection<int, SourceReference>
     */
    private function storeHints(Collection $hints, array $context): Collection
    {
        return $hints
            ->map(fn ($hint) => trim((string) $hint))
            ->filter()
            ->unique()
            ->map(function (string $hint) use ($context) {
                $url = $this->extractUrl($hint);
                $normalized = $this->normalizeLabel($hint);

                $query = SourceReference::query();

                foreach (['script_id', 'news_item_id', 'script_review_item_id', 'editorial_schedule_run_id'] as $field) {
                    if (array_key_exists($field, $context)) {
                        $query->where($field, $context[$field]);
                    }
                }

                if ($url) {
                    $query->where('source_url', $url);
                } else {
                    $query->whereRaw('LOWER(COALESCE(source_name, title, "")) = ?', [Str::lower($normalized)]);
                }

                $existing = $query->first();
                if ($existing) {
                    return null;
                }

                return SourceReference::query()->create([
                    ...$context,
                    'title' => Str::limit($normalized, 255),
                    'source_name' => Str::limit($this->guessSourceName($hint, $url), 255),
                    'source_url' => $url,
                    'source_type' => 'web',
                    'verification_status' => 'pending',
                    'metadata' => ['extracted_hint' => $hint],
                ]);
            })
            ->filter(fn ($item) => $item instanceof SourceReference)
            ->values();
    }

    private function extractUrl(string $text): ?string
    {
        if (! preg_match('/https?:\/\/[^\s)]+/iu', $text, $matches)) {
            return null;
        }

        return rtrim($matches[0], '.,;');
    }

    private function normalizeLabel(string $text): string
    {
        $cleaned = preg_replace('/\s+/u', ' ', trim($text)) ?? trim($text);

        return trim($cleaned, "-:•* \t\n\r\0\x0B");
    }

    private function guessSourceName(string $hint, ?string $url): string
    {
        if ($url) {
            $host = parse_url($url, PHP_URL_HOST);

            if (is_string($host) && $host !== '') {
                return Str::of($host)->replace('www.', '')->__toString();
            }
        }

        return $this->normalizeLabel($hint);
    }
}
