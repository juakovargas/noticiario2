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
use App\Support\Timezones\TimezoneResolver;
use App\Services\Scripts\ScriptProductionMetadataGenerator;

class BulletinPromptRunService
{
    use GeneratesUniqueSlug;

    public function __construct(
        private readonly BulletinPromptGenerator $promptGenerator,
        private readonly AiResponseParser $parser,
        private readonly ScriptProductionMetadataGenerator $metadataGenerator,
    private readonly TimezoneResolver $timezoneResolver,
    ) {
    }

    public function createFromBulletinType(BulletinType $bulletinType, ?int $userId = null, ?string $scheduledForInput = null): BulletinPromptRun
    {
        $bulletinType->loadMissing(['location', 'newsCategory', 'language', 'promptProfile']);

        $profile = $bulletinType->promptProfile
            ?: PromptProfile::query()->where('is_active', true)->where('is_default', true)->first()
            ?: PromptProfile::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('sort_order')->first();

        $scheduledFor = $this->resolveScheduledFor($bulletinType, $scheduledForInput);
        $displayTimezone = $this->timezoneResolver->resolve($bulletinType->default_timezone, $bulletinType->location?->timezone);
        $localizedSchedule = $scheduledFor->copy()->timezone($displayTimezone)->locale('es')->isoFormat('D [de] MMMM [de] YYYY, HH:mm');
        $title = sprintf('%s - %s', $bulletinType->name, $localizedSchedule);

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

        $this->syncEditorialScheduleRun($run->fresh());

        return $prompt;
    }

    public function saveResponse(BulletinPromptRun $run, string $responseText): BulletinPromptRun
    {
        $mode = (string) ($run->bulletinType?->output_mode ?? "plain_final_script");
        $parsed = $this->parser->parse($responseText, $mode);
        if ($mode === 'plain_script') { $mode = 'plain_final_script'; }

        $currentStatus = (string) $run->status;

        $run->update([
            'ai_response_text' => $responseText,
            'parsed_response' => $parsed,
            'response_received_at' => now(),
            'status' => in_array($currentStatus, ['script_created', 'completed', 'archived'], true) ? $currentStatus : 'response_received',
        ]);

        $this->syncEditorialScheduleRun($run->fresh());

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
            $body = trim((string) ($parsed['raw'] ?? $parsed['body'] ?? $response));
            $parsedResponseUsed = false;
        }

        $sourceHints = collect($parsed['source_hints'] ?? [])->whenEmpty(fn ($c) => collect($items)
            ->filter(fn (mixed $item): bool => is_array($item))
            ->flatMap(fn (array $item): array => is_array($item['source_hints'] ?? null) ? $item['source_hints'] : []))
            ->filter(fn (mixed $item): bool => is_array($item))
            ->flatMap(fn (array $item): array => is_array($item['source_hints'] ?? null) ? $item['source_hints'] : [])
            ->map(fn (mixed $hint): string => trim((string) $hint))
            ->filter(fn (string $hint): bool => $hint !== '')
            ->values()
            ->all();

        $script = Script::query()->create([
            'edition_id' => $run->edition_id,
            'bulletin_prompt_run_id' => $run->id,
            'title' => $title,
            'final_title' => trim((string) ($parsed['title'] ?? '')) ?: $title,
            'production_name' => $run->title,
            'status' => 'draft',
            'production_status' => 'draft',
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
                'parser_warnings' => is_array($parsed['warnings'] ?? null) ? $parsed['warnings'] : [],
                'output_mode' => $run->bulletinType?->output_mode ?? 'plain_final_script',
                'response_format' => in_array($run->bulletinType?->output_mode, ['structured_script'], true) ? 'structured_text' : 'plain_text',
                'verification_notes' => $notes !== '' ? $notes : null,
                'notes' => $notes !== '' ? $notes : null,
                'parsed_response' => $parsed,
            ],
        ]);


        $generated = $this->metadataGenerator->generateForScript($script, $run);
        $fillable = ['production_name','final_title','public_description','short_description','hashtags','social_copy','target_platforms','seo_title','seo_description','production_status'];
        $updates = [];
        foreach ($fillable as $field) {
            $current = $script->{$field};
            $empty = is_array($current) ? count($current) === 0 : blank($current);
            if ($empty && array_key_exists($field, $generated)) {
                $updates[$field] = $generated[$field];
            }
        }
        $meta = is_array($script->metadata) ? $script->metadata : [];
        $meta['production_metadata_generated_at'] = now()->toDateTimeString();
        $meta['production_metadata_generated_from'] = 'bulletin_prompt_run';
        $meta['production_metadata_autogenerated'] = true;
        $updates['metadata'] = $meta;
        $script->update($updates);

        $nextStatus = in_array((string) $run->status, ['archived', 'completed'], true)
            ? (string) $run->status
            : 'script_created';

        $run->update([
            'script_id' => $script->id,
            'script_created_at' => now(),
            'status' => $nextStatus,
        ]);

        $this->syncEditorialScheduleRun($run->fresh());

        return $script;
    }

    private function syncEditorialScheduleRun(BulletinPromptRun $run): void
    {
        if (! $run->editorial_schedule_run_id) {
            return;
        }

        $status = match (true) {
            filled($run->script_id) => 'script_created',
            filled($run->ai_response_text) => 'response_received',
            filled($run->generated_prompt) => 'prompt_generated',
            default => 'prompt_run_created',
        };

        $run->editorialScheduleRun()->update([
            'generated_prompt' => $run->generated_prompt,
            'ai_response_text' => $run->ai_response_text,
            'parsed_response' => $run->parsed_response,
            'prompt_generated_at' => $run->prompt_generated_at,
            'response_received_at' => $run->response_received_at,
            'script_id' => $run->script_id,
            'script_created_at' => $run->script_created_at,
            'status' => $status,
            'error_message' => null,
        ]);
    }

    private function resolveScheduledFor(BulletinType $bulletinType, ?string $scheduledForInput): Carbon
    {
        $timezone = $this->timezoneResolver->resolve(
            $bulletinType->default_timezone,
            $bulletinType->location?->timezone ?? optional(auth()->user())->timezone ?? config('app.timezone')
        );

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
