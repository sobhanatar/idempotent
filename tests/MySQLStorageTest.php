<?php

namespace Sobhanatar\Idempotent\Tests;

use Illuminate\Support\Facades\Schema;

class MySQLStorageTest extends TestCase
{
    /**
     * @test
     */
    public function assert_idempotent_table_exists(): void
    {
        $this->loadMigrationsFrom(self::MIGRATION_PATH);
        $this->assertTrue(Schema::hasTable(config('idempotent.table')));
    }

    /**
     * @test
     */
    public function assert_idempotent_table_non_exists(): void
    {
        $this->loadMigrationsFrom(self::MIGRATION_PATH);
        $this->assertFalse(Schema::hasTable('non-exist-table'));
    }
}
