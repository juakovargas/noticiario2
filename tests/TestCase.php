<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->guardTestingDatabaseSafety();
    }

    protected function guardTestingDatabaseSafety(): void
    {
        if (! app()->environment('testing')) {
            return;
        }

        $connection = config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if ($this->isAllowedTestingDatabase($database)) {
            return;
        }

        throw new RuntimeException("Refusing to run tests against non-testing database: {$database}");
    }

    protected function isAllowedTestingDatabase(?string $database): bool
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
