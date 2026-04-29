<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AiProviderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Provider '.$this->faker->unique()->word(),
            'slug' => $this->faker->unique()->slug(),
            'provider_type' => 'openai',
            'client_driver' => 'custom',
            'base_url' => 'https://api.openai.com/v1',
            'api_key_env_name' => 'OPENAI_API_KEY',
            'default_model' => 'gpt-4o-mini',
            'is_active' => true,
            'is_default' => false,
            'timeout_seconds' => 60,
        ];
    }
}
