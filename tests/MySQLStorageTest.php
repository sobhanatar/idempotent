<?php

namespace Sobhanatar\Idempotent\Tests;

use Illuminate\Support\Facades\Schema;
use Sobhanatar\Idempotent\Exceptions\TableNotFoundException;

class MySQLStorageTest extends TestCase
{
    /**
     * @test
     */
    public function assert_it_can_connect_to_mysql(): void
    {
        $this->assertTrue(true);
    }

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
        $this->expectException(TableNotFoundException::class);
        $this->expectExceptionMessage(
            sprintf(
                "The idempotent table is missing. Make sure `%s` exists and reachable.",
                config('idempotent.table')
            )
        );

        $this->loadMigrationsFrom(self::MIGRATION_PATH);
        $this->assertTrue(Schema::hasTable('non-exist-table'));
    }
}
