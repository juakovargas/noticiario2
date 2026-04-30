<?php

namespace Tests\Unit;

use RuntimeException;
use Tests\TestCase;

class TestingDatabaseSafetyTest extends TestCase
{
    public function test_mysql_testing_database_name_is_allowed(): void
    {
        $this->assertTrue($this->isAllowedTestingDatabase('noticiario2_testing'));
    }

    public function test_sqlite_memory_database_is_allowed(): void
    {
        $this->assertTrue($this->isAllowedTestingDatabase(':memory:'));
    }

    public function test_development_database_name_is_rejected(): void
    {
        $this->assertFalse($this->isAllowedTestingDatabase('noticiario2'));
    }

    public function test_production_database_name_is_rejected(): void
    {
        $this->assertFalse($this->isAllowedTestingDatabase('production'));
    }

    public function test_guard_throws_runtime_exception_for_unsafe_database(): void
    {
        config(['app.env' => 'testing']);
        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.database' => 'noticiario2']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Refusing to run tests against non-testing database: noticiario2');

        $this->guardTestingDatabaseSafety();
    }
}
