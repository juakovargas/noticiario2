<?php

namespace App\Console\Commands;

use App\Models\BulletinPromptRun;
use App\Models\AiProvider;
use App\Services\Pipelines\BulletinPromptRunPipeline;
use Illuminate\Console\Command;

class RunPromptPipelineCommand extends Command
{
    protected $signature = 'noticiario:run-prompt-pipeline {bulletinPromptRunId} {--provider=} {--model=} {--force-prompt} {--force-ai} {--no-metadata} {--no-sources} {--allow-ai} {--dry-run}';
    protected $description = 'Run full bulletin prompt run automation pipeline.';

    public function handle(BulletinPromptRunPipeline $pipeline): int
    {
        $run = BulletinPromptRun::query()->findOrFail((int) $this->argument('bulletinPromptRunId'));
        $provider = $this->option('provider');
        $providerId = is_numeric($provider) ? (int) $provider : AiProvider::query()->where('slug', (string) $provider)->value('id');

        $summary = $pipeline->run($run, null, [
            'ai_provider_id' => $providerId,
            'model' => $this->option('model'),
            'force_regenerate_prompt' => (bool) $this->option('force-prompt'),
            'force_regenerate_ai_response' => (bool) $this->option('force-ai'),
            'generate_metadata' => ! (bool) $this->option('no-metadata'),
            'extract_sources' => ! (bool) $this->option('no-sources'),
            'allow_ai_call' => (bool) $this->option('allow-ai'),
            'dry_run' => (bool) $this->option('dry-run'),
        ]);
        $this->line(json_encode($summary, JSON_PRETTY_PRINT));
        return $summary['success'] ? self::SUCCESS : self::FAILURE;
    }
}
