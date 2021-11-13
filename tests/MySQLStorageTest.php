<?php

namespace Sobhanatar\Idempotent\Tests;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Sobhanatar\Idempotent\Contracts\MysqlStorage;

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
}
