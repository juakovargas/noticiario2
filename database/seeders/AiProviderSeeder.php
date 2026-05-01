<?php

namespace Database\Seeders;

use App\Models\AiProvider;
use Illuminate\Database\Seeder;

class AiProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            ['name'=>'Groq','slug'=>'groq','provider_type'=>'groq','client_driver'=>'custom','provider_category'=>'text','capabilities'=>['script_generation','fast_inference'],'base_url'=>'https://api.groq.com/openai/v1','api_key_env_name'=>'GROQ_API_KEY','default_model'=>'llama-3.3-70b-versatile','is_active'=>true,'is_default'=>false,'supports_grounding'=>false,'supports_citations'=>false,'is_testing'=>false,'is_local'=>false,'timeout_seconds'=>60,'retry_on_rate_limit'=>true,'max_retries'=>3,'initial_retry_delay_seconds'=>3,'max_retry_delay_seconds'=>30,'min_seconds_between_requests'=>1],
            ['name'=>'Gemini Flash','slug'=>'gemini-flash','provider_type'=>'gemini','client_driver'=>'custom','provider_category'=>'text','capabilities'=>['script_generation','text_generation'],'base_url'=>'https://generativelanguage.googleapis.com/v1beta','api_key_env_name'=>'GEMINI_API_KEY','default_model'=>'gemini-2.0-flash','is_active'=>false,'is_default'=>false,'supports_grounding'=>false,'supports_citations'=>false,'is_testing'=>false,'is_local'=>false,'timeout_seconds'=>60,'retry_on_rate_limit'=>true,'max_retries'=>3,'initial_retry_delay_seconds'=>5,'max_retry_delay_seconds'=>60,'min_seconds_between_requests'=>2],
            ['name'=>'Gemini Grounded','slug'=>'gemini-grounded','provider_type'=>'gemini','client_driver'=>'custom','provider_category'=>'grounded_text','capabilities'=>['script_generation','news_grounding','google_search_grounding','citations'],'base_url'=>'https://generativelanguage.googleapis.com/v1beta','api_key_env_name'=>'GEMINI_API_KEY','default_model'=>'gemini-2.0-flash','is_active'=>false,'is_default'=>false,'supports_grounding'=>true,'supports_citations'=>true,'is_testing'=>false,'is_local'=>false,'timeout_seconds'=>60,'requests_per_minute_limit'=>10,'requests_per_day_limit'=>1500,'min_seconds_between_requests'=>6,'retry_on_rate_limit'=>true,'max_retries'=>5,'initial_retry_delay_seconds'=>10,'max_retry_delay_seconds'=>300,'backoff_multiplier'=>2.0,'jitter_enabled'=>true],
            ['name'=>'Mock','slug'=>'mock','provider_type'=>'mock','client_driver'=>'custom','provider_category'=>'testing','capabilities'=>['mock','testing'],'is_active'=>false,'is_default'=>false,'is_testing'=>true,'is_local'=>false,'timeout_seconds'=>15,'retry_on_rate_limit'=>false],
            
            ['name'=>'Edge TTS','slug'=>'edge-tts','provider_type'=>'edge_tts','client_driver'=>'custom','provider_category'=>'audio','capabilities'=>['tts','speech_synthesis'],'is_active'=>false,'is_default'=>false,'is_testing'=>false,'is_local'=>false,'timeout_seconds'=>30],
            ['name'=>'ElevenLabs','slug'=>'elevenlabs','provider_type'=>'elevenlabs','client_driver'=>'custom','provider_category'=>'audio','capabilities'=>['tts','speech_synthesis','voice_cloning'],'api_key_env_name'=>'ELEVENLABS_API_KEY','is_active'=>false,'is_default'=>false,'is_testing'=>false,'is_local'=>false,'timeout_seconds'=>30],
            ['name'=>'Google Cloud TTS','slug'=>'google-cloud-tts','provider_type'=>'google_tts','client_driver'=>'custom','provider_category'=>'audio','capabilities'=>['tts','speech_synthesis'],'is_active'=>false,'is_default'=>false,'is_testing'=>false,'is_local'=>false,'timeout_seconds'=>30],
            ['name'=>'Remotion','slug'=>'remotion','provider_type'=>'remotion','client_driver'=>'custom','provider_category'=>'video','capabilities'=>['video_render','template_render'],'is_active'=>false,'is_default'=>false,'is_testing'=>false,'is_local'=>true,'timeout_seconds'=>60],
            ['name'=>'FFmpeg','slug'=>'ffmpeg','provider_type'=>'ffmpeg','client_driver'=>'custom','provider_category'=>'media','capabilities'=>['media_processing','video_render','audio_processing'],'is_active'=>false,'is_default'=>false,'is_testing'=>false,'is_local'=>true,'timeout_seconds'=>60],
            ['name'=>'Local Ollama','slug'=>'local-ollama','provider_type'=>'ollama','client_driver'=>'custom','provider_category'=>'local','capabilities'=>['local_processing','script_review','reformatting'],'base_url'=>'http://localhost:11434','default_model'=>'llama3.1:8b','is_active'=>false,'is_default'=>false,'is_testing'=>false,'is_local'=>true,'timeout_seconds'=>60,'retry_on_rate_limit'=>false],
        ];
        foreach ($providers as $provider) {
            $existing = AiProvider::query()->where('slug', $provider['slug'])->first();
            if (! $existing) {
                AiProvider::query()->create($provider);
                continue;
            }

            foreach ($provider as $key => $value) {
                if (is_null($existing->{$key} ?? null)) {
                    $existing->{$key} = $value;
                }
            }

            $existing->save();
        }

        $currentDefault = AiProvider::query()->where('is_default', true)->first();
        if (! $currentDefault || $currentDefault->provider_type === 'mock') {
            $groq = AiProvider::query()->where('slug', 'groq')->first();
            if ($groq) {
                AiProvider::query()->where('id', '!=', $groq->id)->update(['is_default' => false]);
                $groq->update(['is_default' => true]);
            }
        }
    }
}
