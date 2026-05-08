<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\Script;
use App\Models\ScriptAudioRender;
use App\Services\Audio\ElevenLabsTextToSpeechService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ScriptAudioRenderController extends Controller
{
    public function store(Script $script, ElevenLabsTextToSpeechService $textToSpeech): RedirectResponse
    {
        $sourceText = $this->sourceTextFor($script);
        $voiceId = $this->configString('services.elevenlabs.default_voice_id');
        $modelId = $this->configString('services.elevenlabs.default_model_id');
        $outputFormat = $this->configString('services.elevenlabs.default_output_format');

        $audioRender = $script->audioRenders()->create([
            'status' => 'pending',
            'provider' => 'elevenlabs',
            'voice_id' => $voiceId,
            'model_id' => $modelId,
            'output_format' => $outputFormat,
            'source_text' => $sourceText,
            'audio_disk' => 'public',
            'character_count' => mb_strlen($sourceText),
            'requested_by' => request()->user()?->id,
        ]);

        try {
            $audio = $textToSpeech->synthesize($sourceText, $voiceId, $modelId, $outputFormat);

            $path = sprintf(
                'scripts/%d/audio/script_%d_audio_%d.mp3',
                $script->id,
                $script->id,
                $audioRender->id
            );

            Storage::disk('public')->put($path, $audio);

            $audioRender->update([
                'status' => 'completed',
                'audio_path' => $path,
                'audio_disk' => 'public',
                'character_count' => mb_strlen($sourceText),
                'error_message' => null,
                'generated_at' => now(),
            ]);

            return back()->with('success', 'Audio generated successfully.');
        } catch (Throwable $exception) {
            $audioRender->update([
                'status' => 'failed',
                'error_message' => Str::limit($exception->getMessage(), 1000, '...'),
            ]);

            return back()->with('error', $this->flashMessageFor($exception));
        }
    }

    public function download(Script $script, ScriptAudioRender $audioRender): StreamedResponse|RedirectResponse
    {
        if ($audioRender->script_id !== $script->id) {
            abort(404);
        }

        if (! $audioRender->audio_path || ! Storage::disk($audioRender->audio_disk)->exists($audioRender->audio_path)) {
            return back()->with('error', 'Audio file not found.');
        }

        return Storage::disk($audioRender->audio_disk)->download($audioRender->audio_path);
    }

    private function sourceTextFor(Script $script): string
    {
        return collect([$script->intro, $script->body, $script->outro])
            ->map(fn (?string $part): string => trim((string) $part))
            ->filter()
            ->implode("\n\n");
    }

    private function configString(string $key): ?string
    {
        $value = trim((string) config($key));

        return $value === '' ? null : $value;
    }

    private function flashMessageFor(Throwable $exception): string
    {
        $message = $exception->getMessage();

        return in_array($message, [
            'The script has no text to convert into audio.',
            'ElevenLabs API key is not configured.',
            'ElevenLabs voice ID is not configured.',
            'ElevenLabs model ID is not configured.',
            'ElevenLabs returned an empty audio response.',
        ], true)
            ? $message
            : 'Audio generation failed.';
    }
}
