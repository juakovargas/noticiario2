<?php

namespace App\Console\Commands;

use App\Models\AiProvider;
use App\Models\AiRequestLog;
use App\Services\Ai\AiClientManager;
use Illuminate\Console\Command;

class TestAiProviderCommand extends Command
{
    protected $signature = 'noticiario:test-ai-provider {provider? : Provider slug, for example groq} {--model= : Override model} {--prompt= : Prompt text} {--dry-run : Validate configuration without making request}';
    protected $description = 'Safely test an AI provider configuration.';

    public function handle(AiClientManager $manager): int
    {
        $slug = (string) ($this->argument('provider') ?: 'groq');
        $provider = AiProvider::query()->where('slug', $slug)->first();
        if (! $provider) { $this->error('Provider test failed: provider not found.'); return self::FAILURE; }
        if ($provider->requiresApiKey() && ! $provider->hasConfiguredApiKey()) { $this->error('Environment key missing.'); return self::FAILURE; }
        $this->info('Provider configuration test');
        $this->line('Provider: '.$provider->name.' ('.$provider->slug.')');
        $this->line('Environment key configured: '.($provider->hasConfiguredApiKey() ? 'yes' : 'no'));
        if ($this->option('dry-run')) { $this->info('Dry run complete.'); return self::SUCCESS; }
        $prompt = (string) ($this->option('prompt') ?: 'Return exactly: OK');
        $model = $this->option('model') ?: $provider->default_model;
        try {
            $res = $manager->generateText($provider, $prompt, ['model'=>$model]);
            AiRequestLog::query()->create(['ai_provider_id'=>$provider->id,'model'=>$res->model,'status'=>'success','request_type'=>'manual_test','prompt_hash'=>hash('sha256',$prompt),'prompt_preview'=>str($prompt)->limit(500)->toString(),'response_preview'=>str($res->text)->limit(500)->toString(),'input_tokens'=>$res->inputTokens,'output_tokens'=>$res->outputTokens,'total_tokens'=>$res->totalTokens,'duration_ms'=>$res->durationMs,'started_at'=>now(),'completed_at'=>now()]);
            $this->info('Provider test successful');
            $this->line('Preview: '.str($res->text)->limit(120));
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Provider test failed: '.str($e->getMessage())->limit(180));
            return self::FAILURE;
        }
    }
}
