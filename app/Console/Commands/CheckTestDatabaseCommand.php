<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckTestDatabaseCommand extends Command
{
    protected $signature = 'noticiario:check-test-database';

    protected $description = 'Check whether current test database configuration is safe.';

    public function handle(): int
    {
        $environment = (string) config('app.env');
        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        $safe = $this->isSafeDatabaseName($database);

        $this->line("APP_ENV={$environment}");
        $this->line("DB_CONNECTION={$connection}");
        $this->line("DB_DATABASE={$database}");
        $this->line('SAFE=' . ($safe ? 'yes' : 'no'));

        if ($safe) {
            $this->info('Testing database configuration is safe.');

            return self::SUCCESS;
        }

        $this->error('Testing database configuration is unsafe.');

        return self::FAILURE;
    }

    private function isSafeDatabaseName(?string $database): bool
    {
        if (! is_string($database) || $database === '') {
            return false;
        }

        if ($database === ':memory:') {
            return true;
        }

        return str_contains($database, '_testing') || str_ends_with($database, '_test');
    }
}
