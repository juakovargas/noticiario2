<?php

namespace App\Services\PromptGeneration;

use App\Models\BulletinPromptRun;
use App\Models\BulletinType;
use App\Models\Edition;
use App\Models\PromptProfile;
use App\Models\Script;
use App\Services\EditorialScheduling\AiResponseParser;
use App\Support\GeneratesUniqueSlug;
use Carbon\Carbon;

class BulletinPromptRunService
{
    use GeneratesUniqueSlug;

    public function __construct(
        private readonly BulletinPromptGenerator $promptGenerator,
        private readonly AiResponseParser $parser,
    ) {
    }

    public function createFromBulletinType(BulletinType $bulletinType, ?int $userId = null, ?string $scheduledForInput = null): BulletinPromptRun
    {
        $bulletinType->loadMissing(['location', 'newsCategory', 'language', 'promptProfile']);

        $profile = $bulletinType->promptProfile
            ?: PromptProfile::query()->where('is_active', true)->where('is_default', true)->first()
            ?: PromptProfile::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('sort_order')->first();

        $scheduledFor = $this->resolveScheduledFor($bulletinType, $scheduledForInput);
        $title = sprintf('%s - %s', $bulletinType->name, $scheduledFor->format('Y-m-d H:i'));

        $edition = Edition::query()->create([
            'location_id' => $bulletinType->location_id,
            'title' => $title,
            'slug' => $this->uniqueSlug(Edition::class, $title),
            'edition_type' => $bulletinType->edition_type ?? 'special',
            'scheduled_for' => $scheduledFor,
            'language' => $bulletinType->language?->code,
            'status' => 'planning',
            'target_duration_seconds' => $bulletinType->target_duration_seconds,
            'description' => $bulletinType->description,
            'metadata' => [
                'created_from' => 'bulletin_prompt_run',
                'bulletin_type_id' => $bulletinType->id,
            ],
        ]);

        return BulletinPromptRun::query()->create([
            'bulletin_type_id' => $bulletinType->id,
            'prompt_profile_id' => $profile?->id,
            'created_by' => $userId,
            'title' => $title,
            'scheduled_for' => $scheduledFor,
            'edition_id' => $edition->id,
            'status' => 'draft',
        ]);
    }

    public function generatePrompt(BulletinPromptRun $run): string
    {
        $prompt = $this->promptGenerator->generate($run);

        $run->update([
            'generated_prompt' => $prompt,
            'prompt_generated_at' => now(),
            'status' => 'prompt_ready',
        ]);

        return $prompt;
    }

    public function saveResponse(BulletinPromptRun $run, string $responseText): BulletinPromptRun
    {
        $parsed = $this->parser->parse($responseText);

        $run->update([
            'ai_response_text' => $responseText,
            'parsed_response' => $parsed,
            'response_received_at' => now(),
            'status' => 'response_received',
        ]);

        return $run->refresh();
    }

    public function createScript(BulletinPromptRun $run): Script
    {
        $run->loadMissing(['script', 'edition', 'bulletinType.language']);

        if ($run->script_id && $run->script) {
            return $run->script;
        }

        $response = trim((string) $run->ai_response_text);
        $parsed = is_array($run->parsed_response) ? $run->parsed_response : [];
        $items = is_array($parsed['items'] ?? null) ? $parsed['items'] : [];

        $intro = trim((string) ($parsed['intro'] ?? ''));
        $outro = trim((string) ($parsed['outro'] ?? ''));
        $notes = trim((string) ($parsed['notes'] ?? ''));
        $title = trim((string) ($parsed['title'] ?? '')) ?: ($run->edition?->title ?? $run->title);

        [$body, $parsedResponseUsed] = $this->bodyFromParsedItems($items);
        if ($body === '') {
            $body = trim((string) ($parsed['body'] ?? $response));
        }

        $sourceHints = collect($items)
            ->filter(fn (mixed $item): bool => is_array($item))
            ->flatMap(fn (array $item): array => is_array($item['source_hints'] ?? null) ? $item['source_hints'] : [])
            ->map(fn (mixed $hint): string => trim((string) $hint))
            ->filter(fn (string $hint): bool => $hint !== '')
            ->values()
            ->all();

        $script = Script::query()->create([
            'edition_id' => $run->edition_id,
            'title' => $title,
            'status' => 'draft',
            'language' => $run->edition?->language ?: $run->bulletinType?->language?->code,
            'intro' => $intro,
            'body' => $body,
            'outro' => $outro,
            'estimated_duration_seconds' => $run->bulletinType?->target_duration_seconds,
            'metadata' => [
                'created_from' => 'bulletin_prompt_run',
                'bulletin_prompt_run_id' => $run->id,
                'bulletin_type_id' => $run->bulletin_type_id,
                'prompt_profile_id' => $run->prompt_profile_id,
                'parsed_response_used' => $parsedResponseUsed,
                'news_item_count' => count($items),
                'source_hints' => $sourceHints,
                'notes' => $notes !== '' ? $notes : null,
                'parsed_response' => $parsed,
            ],
        ]);

        $run->update([
            'script_id' => $script->id,
            'script_created_at' => now(),
            'status' => 'script_created',
        ]);

        return $script;
    }

    private function resolveScheduledFor(BulletinType $bulletinType, ?string $scheduledForInput): Carbon
    {
        $timezone = $bulletinType->default_timezone ?: config('app.timezone');

        if ($scheduledForInput) {
            return Carbon::parse($scheduledForInput, $timezone)->startOfMinute()->utc();
        }

        if ($bulletinType->default_schedule_time) {
            return now($timezone)->setTimeFromTimeString($bulletinType->default_schedule_time)->startOfMinute()->utc();
        }

        return now($timezone)->startOfMinute()->utc();
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array{0:string,1:bool}
     */
    private function bodyFromParsedItems(array $items): array
    {
        $blocks = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $script = trim((string) ($item['script'] ?? ''));
            if ($script === '') {
                continue;
            }

            $headline = trim((string) ($item['headline'] ?? ''));
            $blocks[] = trim(($headline !== '' ? '['.$headline."]\n" : '').$script);
        }

        $body = trim(implode("\n\n", $blocks));

        return [$body, $body !== ''];
    }
}
