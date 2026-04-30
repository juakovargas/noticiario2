<?php

namespace Database\Seeders;

use App\Models\AiProvider;
use Illuminate\Database\Seeder;

class AiProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            ['name' => 'OpenRouter','slug' => 'openrouter','provider_type' => 'openrouter','client_driver' => 'custom','base_url' => 'https://openrouter.ai/api/v1','api_key_env_name' => 'OPENROUTER_API_KEY','default_model' => 'openai/gpt-4o-mini','is_active' => false,'timeout_seconds' => 60],
            ['name' => 'OpenAI','slug' => 'openai','provider_type' => 'openai','client_driver' => 'custom','base_url' => 'https://api.openai.com/v1','api_key_env_name' => 'OPENAI_API_KEY','default_model' => 'gpt-4o-mini','is_active' => false,'timeout_seconds' => 60],
            ['name' => 'Groq','slug' => 'groq','provider_type' => 'groq','client_driver' => 'custom','base_url' => 'https://api.groq.com/openai/v1','api_key_env_name' => 'GROQ_API_KEY','default_model' => 'llama-3.3-70b-versatile','is_active' => true,'timeout_seconds' => 60,'max_tokens' => 3000,'temperature' => 0.4,'daily_request_limit' => 1000,'monthly_request_limit' => 30000],
            ['name' => 'Local Ollama','slug' => 'local-ollama','provider_type' => 'ollama','client_driver' => 'custom','base_url' => 'http://localhost:11434','api_key_env_name' => null,'default_model' => 'llama3.1:8b','is_active' => false,'timeout_seconds' => 60],
            ['name' => 'Mock Provider','slug' => 'mock','provider_type' => 'mock','client_driver' => 'custom','base_url' => null,'api_key_env_name' => null,'default_model' => 'mock-model','is_active' => app()->environment('testing'),'timeout_seconds' => 30],
        ];

        $hasDefault = AiProvider::query()->where('is_default', true)->exists();

        foreach ($providers as $providerData) {
            $existing = AiProvider::query()->where('slug', $providerData['slug'])->first();
            $defaultFlag = $existing?->is_default ?? false;

            $provider = AiProvider::query()->updateOrCreate(['slug' => $providerData['slug']], array_merge($providerData, [
                'is_default' => $defaultFlag,
            ]));

            if ($provider->slug === 'groq' && ! $hasDefault && ! $provider->is_default) {
                AiProvider::query()->where('is_default', true)->update(['is_default' => false]);
                $provider->update(['is_default' => true]);
                $hasDefault = true;
            }
        }
    }
}
