<?php

namespace App\Services\Audio;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ElevenLabsTextToSpeechService
{
    public function synthesize(
        string $text,
        ?string $voiceId = null,
        ?string $modelId = null,
        ?string $outputFormat = null,
    ): string {
        $apiKey = trim((string) config('services.elevenlabs.api_key'));

        if ($apiKey === '') {
            throw new RuntimeException('ElevenLabs API key is not configured.');
        }

        $text = trim($text);
        if ($text === '') {
            throw new RuntimeException('The script has no text to convert into audio.');
        }

        $voiceId = trim((string) ($voiceId ?: config('services.elevenlabs.default_voice_id')));
        if ($voiceId === '') {
            throw new RuntimeException('ElevenLabs voice ID is not configured.');
        }

        $modelId = trim((string) ($modelId ?: config('services.elevenlabs.default_model_id')));
        if ($modelId === '') {
            throw new RuntimeException('ElevenLabs model ID is not configured.');
        }

        $outputFormat = trim((string) ($outputFormat ?: config('services.elevenlabs.default_output_format')));

        $url = 'https://api.elevenlabs.io/v1/text-to-speech/'.rawurlencode($voiceId);
        if ($outputFormat !== '') {
            $url .= '?'.http_build_query(['output_format' => $outputFormat], '', '&', PHP_QUERY_RFC3986);
        }

        $response = Http::timeout(120)
            ->withHeaders([
                'xi-api-key' => $apiKey,
                'Accept' => 'audio/mpeg',
                'Content-Type' => 'application/json',
            ])
            ->post($url, [
                'text' => $text,
                'model_id' => $modelId,
            ]);

        if (! $response->successful()) {
            $message = data_get($response->json(), 'detail.message')
                ?? data_get($response->json(), 'message')
                ?? $response->body();

            throw new RuntimeException(sprintf(
                'ElevenLabs request failed with status %s: %s',
                $response->status(),
                str((string) $message)->limit(300, '...')
            ));
        }

        $audio = $response->body();
        if ($audio === '') {
            throw new RuntimeException('ElevenLabs returned an empty audio response.');
        }

        return $audio;
    }
}
