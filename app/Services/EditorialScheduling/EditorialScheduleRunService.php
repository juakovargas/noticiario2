<?php

namespace App\Services\EditorialScheduling;

use App\Models\Edition;
use App\Models\EditorialSchedule;
use App\Models\EditorialScheduleRun;
use App\Models\Script;
use App\Support\GeneratesUniqueSlug;
use Carbon\Carbon;
use Illuminate\Support\Str;

class EditorialScheduleRunService
{
    use GeneratesUniqueSlug;

    public function createRunFromSchedule(EditorialSchedule $schedule, ?Carbon $scheduledFor = null): EditorialScheduleRun
    {
        $scheduled = $scheduledFor ?? $this->resolveScheduledFor($schedule);

        $edition = Edition::query()->create([
            'title' => sprintf('%s - %s', $schedule->name, $scheduled?->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i')),
            'slug' => $this->uniqueSlug(Edition::class, $schedule->name.' '.($scheduled?->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i'))),
            'location_id' => $schedule->location_id,
            'edition_type' => $schedule->edition_type,
            'scheduled_for' => $scheduled,
            'language' => $schedule->language?->code,
            'status' => 'planning',
            'target_duration_seconds' => $schedule->target_duration_seconds,
            'description' => $schedule->description ?: $schedule->editorial_instructions,
            'metadata' => [
                'created_from' => 'editorial_schedule',
                'editorial_schedule_id' => $schedule->id,
            ],
        ]);

        return EditorialScheduleRun::query()->create([
            'editorial_schedule_id' => $schedule->id,
            'edition_id' => $edition->id,
            'scheduled_for' => $scheduled,
            'status' => 'pending',
        ]);
    }

    public function generatePrompt(EditorialScheduleRun $run): string
    {
        $run->loadMissing(['schedule.location', 'schedule.newsCategory', 'schedule.language', 'schedule.aiPromptTemplate']);
        $schedule = $run->schedule;

        $payload = [
            '{{schedule_name}}' => $schedule->name,
            '{{location_name}}' => $schedule->location?->name ?? 'Global',
            '{{category_name}}' => $schedule->newsCategory?->name ?? 'General',
            '{{language_name}}' => $schedule->language?->name ?? 'Unspecified',
            '{{language_code}}' => $schedule->language?->code ?? '',
            '{{edition_type}}' => $schedule->edition_type,
            '{{scheduled_for}}' => optional($run->scheduled_for)->toDateTimeString() ?? '',
            '{{target_duration_seconds}}' => (string) ($schedule->target_duration_seconds ?? ''),
            '{{tone}}' => $schedule->tone ?? 'professional and concise',
            '{{editorial_instructions}}' => $schedule->editorial_instructions ?? '',
            '{{output_instructions}}' => $schedule->output_instructions ?? '',
            '{{date}}' => now()->toDateString(),
        ];

        $template = trim((string) ($schedule->aiPromptTemplate?->user_prompt));
        if ($template !== '') {
            $prompt = str_replace(array_keys($payload), array_values($payload), $template);
        } else {
            $prompt = $this->defaultPrompt($run);
        }

        $run->update([
            'generated_prompt' => trim($prompt),
            'prompt_generated_at' => now(),
            'status' => 'prompt_ready',
        ]);

        return trim($prompt);
    }

    public function receiveAiResponse(EditorialScheduleRun $run, string $responseText): EditorialScheduleRun
    {
        $run->update([
            'ai_response_text' => $responseText,
            'response_received_at' => now(),
            'status' => 'response_received',
        ]);

        return $run->refresh();
    }

    public function createScriptFromResponse(EditorialScheduleRun $run): Script
    {
        $run->loadMissing(['schedule.language', 'edition', 'script']);

        if ($run->script_id && $run->script) {
            return $run->script;
        }

        $response = trim((string) $run->ai_response_text);
        [$intro, $body, $outro] = $this->parseScriptSections($response);

        $script = Script::query()->create([
            'edition_id' => $run->edition_id,
            'title' => $run->edition?->title ?? ($run->schedule->name.' Script'),
            'status' => 'draft',
            'language' => $run->edition?->language ?: $run->schedule?->language?->code,
            'intro' => $intro,
            'body' => $body,
            'outro' => $outro,
            'estimated_duration_seconds' => $run->schedule->target_duration_seconds,
            'metadata' => [
                'created_from' => 'manual_ai_schedule_run',
                'editorial_schedule_id' => $run->editorial_schedule_id,
                'editorial_schedule_run_id' => $run->id,
            ],
        ]);

        $run->update([
            'script_id' => $script->id,
            'script_created_at' => now(),
            'status' => 'script_created',
        ]);

        return $script;
    }

    private function resolveScheduledFor(EditorialSchedule $schedule): Carbon
    {
        $timezone = $schedule->timezone ?: config('app.timezone');

        if ($schedule->frequency_type === 'once' && $schedule->scheduled_date && $schedule->scheduled_time) {
            return Carbon::parse($schedule->scheduled_date->toDateString().' '.$schedule->scheduled_time, $timezone)->utc();
        }

        $time = $schedule->scheduled_time ?: '08:00';

        return Carbon::now($timezone)
            ->setTimeFromTimeString(Str::length($time) === 5 ? $time.':00' : $time)
            ->utc();
    }

    private function defaultPrompt(EditorialScheduleRun $run): string
    {
        $schedule = $run->schedule;

        return trim(implode("\n", [
            'You are preparing a short digital news bulletin script for presenter narration.',
            'Use relevant and recent topics. Avoid invented facts. If available, use web/current information.',
            '',
            'Context:',
            '- Schedule: '.$schedule->name,
            '- Location: '.($schedule->location?->name ?? 'Global'),
            '- Category: '.($schedule->newsCategory?->name ?? 'General'),
            '- Language: '.($schedule->language?->name ?? 'Unspecified').($schedule->language?->code ? ' ('.$schedule->language?->code.')' : ''),
            '- Edition type: '.$schedule->edition_type,
            '- Scheduled for: '.(optional($run->scheduled_for)->toDateTimeString() ?? 'N/A'),
            '- Target duration seconds: '.($schedule->target_duration_seconds ?? 'N/A'),
            '- Tone: '.($schedule->tone ?? 'professional and concise'),
            '',
            'Editorial instructions:',
            $schedule->editorial_instructions ?: 'No additional editorial instructions.',
            '',
            'Output instructions:',
            $schedule->output_instructions ?: 'Concise format ready for presenter narration.',
            '',
            'Return this structure:',
            'Title:',
            'Intro:',
            'Body:',
            'Outro:',
            'Suggested headlines/items:',
            'Sources or source hints:',
        ]));
    }

    private function parseScriptSections(string $response): array
    {
        if ($response === '') {
            return ['', '', ''];
        }

        preg_match('/Intro:\s*(.*?)\s*(?:Body:|$)/is', $response, $introMatch);
        preg_match('/Body:\s*(.*?)\s*(?:Outro:|$)/is', $response, $bodyMatch);
        preg_match('/Outro:\s*(.*)$/is', $response, $outroMatch);

        $intro = trim($introMatch[1] ?? '');
        $body = trim($bodyMatch[1] ?? $response);
        $outro = trim($outroMatch[1] ?? '');

        return [$intro, $body, $outro];
    }
}
