<?php

namespace App\Services\EditorialReview;

use App\Models\BulletinPromptRun;
use App\Models\NewsItem;
use App\Models\Script;
use App\Models\ScriptReviewItem;
use App\Models\SourceReference;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SourceReferenceExtractor
{
    public function extractFromBulletinPromptRun(BulletinPromptRun $run): Collection
    {
        $hints = $this->parsedResponseHints(is_array($run->parsed_response) ? $run->parsed_response : [])
            ->merge($this->linesFromValue($run->ai_response_text))
            ->merge($this->sourceSectionLines((string) $run->ai_response_text));

        return $this->storeHints($hints, [
            'bulletin_prompt_run_id' => $run->id,
            'script_id' => $run->script_id,
            'news_item_id' => null,
            'edition_id' => $run->edition_id,
            'script_review_item_id' => null,
            'editorial_schedule_run_id' => null,
        ]);
    }

    public function extractFromScript(Script $script): Collection
    {
        $script->loadMissing(['reviewItems']);

        $created = $this->storeHints($this->scriptHints($script), [
            'bulletin_prompt_run_id' => null,
            'script_id' => $script->id,
            'news_item_id' => null,
            'edition_id' => $script->edition_id,
            'script_review_item_id' => null,
            'editorial_schedule_run_id' => null,
        ]);

        foreach ($script->reviewItems as $item) {
            $created = $created->merge($this->extractFromReviewItem($item));
        }

        return $created->values();
    }

    public function extractFromNewsItem(NewsItem $newsItem): Collection
    {
        $hints = collect([$newsItem->source_url])
            ->merge($this->linesFromValue($newsItem->summary))
            ->merge($this->linesFromValue($newsItem->body));

        return $this->storeHints($hints, [
            'bulletin_prompt_run_id' => null,
            'script_id' => null,
            'news_item_id' => $newsItem->id,
            'edition_id' => null,
            'script_review_item_id' => null,
            'editorial_schedule_run_id' => null,
        ]);
    }

    public function extractFromReviewItem(ScriptReviewItem $item): Collection
    {
        $hints = collect($item->source_hints ?? [])
            ->merge($this->linesFromValue($item->content))
            ->values();

        return $this->storeHints($hints, [
            'bulletin_prompt_run_id' => null,
            'script_id' => $item->script_id,
            'news_item_id' => $item->news_item_id,
            'edition_id' => $item->script?->edition_id,
            'script_review_item_id' => $item->id,
            'editorial_schedule_run_id' => null,
        ]);
    }

    public function extractFromParsedResponse(array $parsedResponse, ?BulletinPromptRun $run = null, ?Script $script = null): Collection
    {
        return $this->storeHints($this->parsedResponseHints($parsedResponse), [
            'bulletin_prompt_run_id' => $run?->id,
            'script_id' => $script?->id,
            'news_item_id' => null,
            'edition_id' => $run?->edition_id ?? $script?->edition_id,
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
            $hints = $hints->merge($this->linesFromValue($text));
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
                if (! is_array($item)) {
                    continue;
                }

                if (is_array($item['source_hints'] ?? null)) {
                    $hints = $hints->merge($item['source_hints']);
                }

                foreach (['script', 'content', 'summary', 'title', 'headline', 'raw'] as $field) {
                    $hints = $hints->merge($this->linesFromValue($item[$field] ?? null));
                }
            }
        }

        foreach (['raw_response', 'response_text', 'text', 'body'] as $field) {
            $hints = $hints->merge($this->sourceSectionLines((string) ($parsedResponse[$field] ?? '')));
        }

        return $hints;
    }

    private function sourceSectionLines(string $text): Collection
    {
        if ($text === '') {
            return collect();
        }

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

            if (preg_match('/^(SOURCE\s+HINTS|SOURCES|FUENTES|PISTAS\s+DE\s+FUENTES)\s*:?/iu', $trimmed)) {
                $collecting = true;
                continue;
            }

            if ($collecting) {
                $hints->push($trimmed);
            }
        }

        return $hints;
    }

    private function linesFromValue(mixed $value): Collection
    {
        if (! is_string($value)) {
            return collect();
        }

        preg_match_all('/https?:\/\/[^\s)]+/iu', $value, $matches);

        $lines = collect(preg_split('/\r\n|\r|\n/u', $value) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter(fn (string $line) => $line !== '' && (preg_match('/\b(source|sources|fuente|fuentes)\b/i', $line) || str_contains($line, 'http')))
            ->values();

        return $lines->merge($matches[0] ?? []);
    }

    private function storeHints(Collection $hints, array $context): Collection
    {
        return $hints->map(fn ($hint) => trim((string) $hint))
            ->filter()
            ->unique()
            ->map(function (string $hint) use ($context) {
                $url = $this->extractUrl($hint);
                $domain = $this->extractDomain($url);
                $label = $this->normalizeLabel($url ? trim(str_replace($url, '', $hint), " -:|\t") : $hint);
                $name = $this->guessSourceName($hint, $url, $label, $domain);

                $query = SourceReference::query();
                foreach (['bulletin_prompt_run_id', 'script_id', 'news_item_id', 'script_review_item_id', 'edition_id'] as $field) {
                    $query->where($field, $context[$field] ?? null);
                }

                if ($url) {
                    $query->where('source_url', $url);
                } else {
                    $query->whereRaw('LOWER(COALESCE(source_name, title, "")) = ?', [Str::lower($name ?: $label)]);
                }

                if ($query->exists()) {
                    return null;
                }

                return SourceReference::query()->create([
                    ...$context,
                    'title' => Str::limit($label ?: $name, 255),
                    'source_name' => Str::limit($name, 255),
                    'source_url' => $url,
                    'source_domain' => $domain,
                    'source_type' => 'web',
                    'verification_status' => 'pending',
                    'metadata' => ['raw_hint' => $hint],
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

    private function guessSourceName(string $hint, ?string $url, string $label, ?string $domain = null): string
    {
        if ($label !== '') {
            return $label;
        }

        if ($domain !== null && $domain !== '') {
            return $domain;
        }

        if ($url) {
            $host = parse_url($url, PHP_URL_HOST);
            if (is_string($host) && $host !== '') {
                return Str::of($host)->replace('www.', '')->toString();
            }
        }

        return $this->normalizeLabel($hint);
    }

    private function extractDomain(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return null;
        }

        return Str::lower(Str::of($host)->replace('www.', '')->toString());
    }
}
