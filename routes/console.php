<?php

use App\Console\Commands\CreateDueEditorialRunsCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::starting(function () {
    Artisan::resolveCommands([
        CreateDueEditorialRunsCommand::class,
    ]);
});
