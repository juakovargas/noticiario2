<?php

namespace Database\Seeders;

use App\Models\AiProvider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class AiProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            [
                'name' => 'Groq',
                'slug' => 'groq',
                'provider_type' => 'groq',
                'client_driver' => 'custom',
                'provider_category' => 'text',
                'capabilities' => [
                    'script_generation',
                    'fast_inference',
                ],
                'base_url' => 'https://api.groq.com/openai/v1',
                'api_key_env_name' => 'GROQ_API_KEY',
                'default_model' => 'llama-3.3-70b-versatile',
                'is_active' => true,
                'is_default' => false,
                'supports_grounding' => false,
                'supports_citations' => false,
                'supports_streaming' => false,
                'is_testing' => false,
                'is_local' => false,
                'timeout_seconds' => 60,
                'max_tokens' => 3000,
                'temperature' => 0.4,
                'daily_request_limit' => 1000,
                'monthly_request_limit' => 30000,
                'input_token_cost' => 0,
                'output_token_cost' => 0,
                'requests_per_minute_limit' => null,
                'requests_per_day_limit' => 1000,
                'tokens_per_minute_limit' => null,
                'min_seconds_between_requests' => 1,
                'retry_on_rate_limit' => true,
                'max_retries' => 3,
                'initial_retry_delay_seconds' => 3,
                'max_retry_delay_seconds' => 30,
                'backoff_multiplier' => 2.0,
                'jitter_enabled' => true,
            ],
            [
                'name' => 'Gemini Flash',
                'slug' => 'gemini-flash',
                'provider_type' => 'gemini',
                'client_driver' => 'custom',
                'provider_category' => 'text',
                'capabilities' => [
                    'script_generation',
                    'text_generation',
                ],
                'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
                'api_key_env_name' => 'GEMINI_API_KEY',
                'default_model' => 'gemini-3-flash-preview',
                'is_active' => false,
                'is_default' => false,
                'supports_grounding' => false,
                'supports_citations' => false,
                'supports_streaming' => false,
                'is_testing' => false,
                'is_local' => false,
                'timeout_seconds' => 60,
                'max_tokens' => 3000,
                'temperature' => 0.4,
                'daily_request_limit' => 1500,
                'monthly_request_limit' => 45000,
                'input_token_cost' => 0,
                'output_token_cost' => 0,
                'requests_per_minute_limit' => 10,
                'requests_per_day_limit' => 1500,
                'tokens_per_minute_limit' => 1000000,
                'min_seconds_between_requests' => 8,
                'retry_on_rate_limit' => true,
                'max_retries' => 3,
                'initial_retry_delay_seconds' => 10,
                'max_retry_delay_seconds' => 180,
                'backoff_multiplier' => 2.0,
                'jitter_enabled' => true,
            ],
            [
                'name' => 'DeepSeek',
                'slug' => 'deepseek-v4',
                'provider_type' => 'text',
                'client_driver' => 'custom',
                'provider_category' => 'text',
                'capabilities' => [
                    'script_generation',
                    'text_generation',
                ],
                'base_url' => 'https://api.deepseek.com',
                'api_key_env_name' => 'DEEPSEEK_API_KEY',
                'default_model' => 'deepseek-v4-flash',
                'is_active' => true,
                'is_default' => false,
                'supports_grounding' => false,
                'supports_citations' => false,
                'supports_streaming' => false,
                'is_testing' => false,
                'is_local' => false,
                'timeout_seconds' => 90,
                'max_tokens' => 3000,
                'temperature' => 0.4,
                'daily_request_limit' => 1000,
                'monthly_request_limit' => 30000,
                'requests_per_minute_limit' => 6,
                'requests_per_day_limit' => 1000,
                'tokens_per_minute_limit' => null,
                'min_seconds_between_requests' => 2,
                'retry_on_rate_limit' => true,
                'max_retries' => 3,
                'initial_retry_delay_seconds' => 3,
                'max_retry_delay_seconds' => 60,
                'backoff_multiplier' => 2.0,
                'jitter_enabled' => true,
            ],
            [
                'name' => 'Gemini Grounded',
                'slug' => 'gemini-grounded',
                'provider_type' => 'gemini',
                'client_driver' => 'custom',
                'provider_category' => 'grounded_text',
                'capabilities' => [
                    'script_generation',
                    'text_generation',
                    'news_grounding',
                    'google_search_grounding',
                    'citations',
                ],
                'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
                'api_key_env_name' => 'GEMINI_API_KEY',
                'default_model' => 'gemini-3-flash-preview',
                'is_active' => false,
                'is_default' => false,
                'supports_grounding' => true,
                'supports_citations' => true,
                'supports_streaming' => false,
                'is_testing' => false,
                'is_local' => false,
                'timeout_seconds' => 90,
                'max_tokens' => 3000,
                'temperature' => 0.4,
                'daily_request_limit' => 1500,
                'monthly_request_limit' => 45000,
                'input_token_cost' => 0,
                'output_token_cost' => 0,
                'requests_per_minute_limit' => 6,
                'requests_per_day_limit' => 1500,
                'tokens_per_minute_limit' => 1000000,
                'min_seconds_between_requests' => 12,
                'retry_on_rate_limit' => true,
                'max_retries' => 3,
                'initial_retry_delay_seconds' => 20,
                'max_retry_delay_seconds' => 300,
                'backoff_multiplier' => 2.0,
                'jitter_enabled' => true,
            ],
            [
                'name' => 'Mock',
                'slug' => 'mock',
                'provider_type' => 'mock',
                'client_driver' => 'custom',
                'provider_category' => 'testing',
                'capabilities' => [
                    'mock',
                    'testing',
                ],
                'base_url' => null,
                'api_key_env_name' => null,
                'default_model' => 'mock',
                'is_active' => false,
                'is_default' => false,
                'supports_grounding' => false,
                'supports_citations' => false,
                'supports_streaming' => false,
                'is_testing' => true,
                'is_local' => false,
                'timeout_seconds' => 15,
                'max_tokens' => 1000,
                'temperature' => 0,
                'daily_request_limit' => null,
                'monthly_request_limit' => null,
                'input_token_cost' => 0,
                'output_token_cost' => 0,
                'requests_per_minute_limit' => null,
                'requests_per_day_limit' => null,
                'tokens_per_minute_limit' => null,
                'min_seconds_between_requests' => null,
                'retry_on_rate_limit' => false,
                'max_retries' => 0,
                'initial_retry_delay_seconds' => 0,
                'max_retry_delay_seconds' => 0,
                'backoff_multiplier' => 1.0,
                'jitter_enabled' => false,
            ],
            [
                'name' => 'Edge TTS',
                'slug' => 'edge-tts',
                'provider_type' => 'edge_tts',
                'client_driver' => 'custom',
                'provider_category' => 'audio',
                'capabilities' => [
                    'tts',
                    'speech_synthesis',
                ],
                'base_url' => null,
                'api_key_env_name' => null,
                'default_model' => 'edge-tts',
                'is_active' => false,
                'is_default' => false,
                'supports_grounding' => false,
                'supports_citations' => false,
                'supports_streaming' => false,
                'is_testing' => false,
                'is_local' => false,
                'timeout_seconds' => 30,
                'retry_on_rate_limit' => false,
            ],
            [
                'name' => 'ElevenLabs',
                'slug' => 'elevenlabs',
                'provider_type' => 'elevenlabs',
                'client_driver' => 'custom',
                'provider_category' => 'audio',
                'capabilities' => [
                    'tts',
                    'speech_synthesis',
                    'voice_cloning',
                ],
                'base_url' => 'https://api.elevenlabs.io/v1',
                'api_key_env_name' => 'ELEVENLABS_API_KEY',
                'default_model' => 'eleven_multilingual_v2',
                'is_active' => false,
                'is_default' => false,
                'supports_grounding' => false,
                'supports_citations' => false,
                'supports_streaming' => false,
                'is_testing' => false,
                'is_local' => false,
                'timeout_seconds' => 30,
                'retry_on_rate_limit' => true,
                'max_retries' => 2,
                'initial_retry_delay_seconds' => 5,
                'max_retry_delay_seconds' => 60,
                'backoff_multiplier' => 2.0,
                'jitter_enabled' => true,
            ],
            [
                'name' => 'Google Cloud TTS',
                'slug' => 'google-cloud-tts',
                'provider_type' => 'google_tts',
                'client_driver' => 'custom',
                'provider_category' => 'audio',
                'capabilities' => [
                    'tts',
                    'speech_synthesis',
                ],
                'base_url' => 'https://texttospeech.googleapis.com/v1',
                'api_key_env_name' => 'GOOGLE_CLOUD_TTS_API_KEY',
                'default_model' => 'google-cloud-tts',
                'is_active' => false,
                'is_default' => false,
                'supports_grounding' => false,
                'supports_citations' => false,
                'supports_streaming' => false,
                'is_testing' => false,
                'is_local' => false,
                'timeout_seconds' => 30,
                'retry_on_rate_limit' => true,
                'max_retries' => 2,
                'initial_retry_delay_seconds' => 5,
                'max_retry_delay_seconds' => 60,
                'backoff_multiplier' => 2.0,
                'jitter_enabled' => true,
            ],
            [
                'name' => 'Remotion',
                'slug' => 'remotion',
                'provider_type' => 'remotion',
                'client_driver' => 'custom',
                'provider_category' => 'video',
                'capabilities' => [
                    'video_render',
                    'template_render',
                ],
                'base_url' => null,
                'api_key_env_name' => null,
                'default_model' => 'remotion',
                'is_active' => false,
                'is_default' => false,
                'supports_grounding' => false,
                'supports_citations' => false,
                'supports_streaming' => false,
                'is_testing' => false,
                'is_local' => true,
                'timeout_seconds' => 60,
                'retry_on_rate_limit' => false,
            ],
            [
                'name' => 'FFmpeg',
                'slug' => 'ffmpeg',
                'provider_type' => 'ffmpeg',
                'client_driver' => 'custom',
                'provider_category' => 'media',
                'capabilities' => [
                    'media_processing',
                    'video_render',
                    'audio_processing',
                ],
                'base_url' => null,
                'api_key_env_name' => null,
                'default_model' => 'ffmpeg',
                'is_active' => false,
                'is_default' => false,
                'supports_grounding' => false,
                'supports_citations' => false,
                'supports_streaming' => false,
                'is_testing' => false,
                'is_local' => true,
                'timeout_seconds' => 60,
                'retry_on_rate_limit' => false,
            ],
            [
                'name' => 'Local Ollama',
                'slug' => 'local-ollama',
                'provider_type' => 'ollama',
                'client_driver' => 'custom',
                'provider_category' => 'local',
                'capabilities' => [
                    'local_processing',
                    'script_review',
                    'reformatting',
                    'script_generation',
                ],
                'base_url' => 'http://localhost:11434',
                'api_key_env_name' => null,
                'default_model' => 'llama3.1:8b',
                'is_active' => false,
                'is_default' => false,
                'supports_grounding' => false,
                'supports_citations' => false,
                'supports_streaming' => false,
                'is_testing' => false,
                'is_local' => true,
                'timeout_seconds' => 60,
                'max_tokens' => 3000,
                'temperature' => 0.4,
                'retry_on_rate_limit' => false,
            ],
        ];

        foreach ($providers as $provider) {
            $provider = $this->onlyExistingColumns($provider);

            $existing = AiProvider::query()
                ->where('slug', $provider['slug'])
                ->first();

            if (! $existing) {
                AiProvider::query()->create($provider);
                continue;
            }

            $data = $this->mergeProviderData($existing, $provider);

            $existing->forceFill($data)->save();
        }

        $this->ensureSafeDefaultProvider();
    }

    private function onlyExistingColumns(array $provider): array
    {
        return collect($provider)
            ->filter(fn (mixed $value, string $key): bool => Schema::hasColumn('ai_providers', $key))
            ->all();
    }

    private function mergeProviderData(AiProvider $existing, array $provider): array
    {
        $alwaysUpdate = [
            'name',
            'provider_type',
            'client_driver',
            'provider_category',
            'capabilities',
            'base_url',
            'api_key_env_name',
            'default_model',
            'supports_grounding',
            'supports_citations',
            'supports_streaming',
            'is_testing',
            'is_local',
        ];

        $fillIfEmpty = [
            'timeout_seconds',
            'max_tokens',
            'temperature',
            'daily_request_limit',
            'monthly_request_limit',
            'input_token_cost',
            'output_token_cost',
            'requests_per_minute_limit',
            'requests_per_day_limit',
            'tokens_per_minute_limit',
            'min_seconds_between_requests',
            'retry_on_rate_limit',
            'max_retries',
            'initial_retry_delay_seconds',
            'max_retry_delay_seconds',
            'backoff_multiplier',
            'jitter_enabled',
        ];

        $data = [];

        foreach ($alwaysUpdate as $key) {
            if (array_key_exists($key, $provider)) {
                $data[$key] = $provider[$key];
            }
        }

        foreach ($fillIfEmpty as $key) {
            if (! array_key_exists($key, $provider)) {
                continue;
            }

            $currentValue = $existing->{$key} ?? null;

            if ($currentValue === null || $currentValue === '') {
                $data[$key] = $provider[$key];
            }
        }

        /*
         * Do not overwrite is_active/is_default for existing records.
         * Those fields are controlled from Admin once the provider exists.
         */
        foreach (['is_active', 'is_default'] as $key) {
            if (! $existing->exists && array_key_exists($key, $provider)) {
                $data[$key] = $provider[$key];
            }
        }

        return $data;
    }

    private function ensureSafeDefaultProvider(): void
    {
        $currentDefault = AiProvider::query()
            ->where('is_default', true)
            ->first();

        if ($currentDefault && $currentDefault->provider_type !== 'mock') {
            return;
        }

        $groq = AiProvider::query()
            ->where('slug', 'groq')
            ->first();

        if (! $groq) {
            return;
        }

        AiProvider::query()
            ->where('id', '!=', $groq->id)
            ->update(['is_default' => false]);

        $groq->forceFill([
            'is_active' => true,
            'is_default' => true,
        ])->save();
    }
}
