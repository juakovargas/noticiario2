<?php

namespace Database\Seeders;

use App\Models\AiProvider;
use Illuminate\Database\Seeder;

class AiProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            [
                'name' => 'OpenRouter',
                'slug' => 'openrouter',
                'provider_type' => 'openrouter',
                'base_url' => 'https://openrouter.ai/api/v1',
                'api_key_env_name' => 'OPENROUTER_API_KEY',
                'default_model' => 'openai/gpt-4o-mini',
                'is_active' => false,
                'is_default' => false,
                'timeout_seconds' => 60,
            ],
            [
                'name' => 'OpenAI',
                'slug' => 'openai',
                'provider_type' => 'openai',
                'base_url' => 'https://api.openai.com/v1',
                'api_key_env_name' => 'OPENAI_API_KEY',
                'default_model' => 'gpt-4o-mini',
                'is_active' => false,
                'is_default' => false,
                'timeout_seconds' => 60,
            ],
            [
                'name' => 'Local Ollama',
                'slug' => 'local-ollama',
                'provider_type' => 'ollama',
                'base_url' => 'http://localhost:11434',
                'api_key_env_name' => null,
                'default_model' => 'llama3.1:8b',
                'is_active' => false,
                'is_default' => false,
                'timeout_seconds' => 60,
            ],
        ];

        foreach ($providers as $provider) {
            AiProvider::query()->updateOrCreate(
                ['slug' => $provider['slug']],
                $provider,
            );
        }
    }
}
