<?php

namespace App\Console\Commands;

use App\Models\AiProvider;
use Illuminate\Console\Command;

class EnsureGroqDefaultCommand extends Command
{
    protected $signature = 'noticiario:ensure-groq-default';
    protected $description = 'Ensure Groq is active and default, and mock provider is not default.';

    public function handle(): int
    {
        $groq = AiProvider::query()->where('slug', 'groq')->first();
        if (! $groq) {
            $this->error('Groq provider missing.');
            return self::FAILURE;
        }

        AiProvider::query()->where('is_default', true)->update(['is_default' => false]);
        $groq->update(['is_active' => true, 'is_default' => true, 'default_model' => 'llama-3.3-70b-versatile', 'daily_request_limit' => 1000]);

        AiProvider::query()->where('provider_type', 'mock')->update(['is_default' => false]);

        $this->info('Groq is now active/default. Mock is not default.');
        return self::SUCCESS;
    }
}
