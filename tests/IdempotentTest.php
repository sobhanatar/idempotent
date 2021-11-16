<?php

namespace Sobhanatar\Idempotent\Tests;

use Sobhanatar\Idempotent\{Contracts\MysqlStorage, Contracts\RedisStorage, Idempotent};

class IdempotentTest extends TestCase
{
    /**
     * @test
     */
    public function assert_storage_with_no_valid_connection_return_error(): void
    {
        $connection = 'invalid';
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('connection `%s` is not supported', $connection));

        (new Idempotent())->resolveStorageService($connection);
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function assert_storage_with_redis_connection(): void
    {
        $connection = 'redis';
        $service = (new Idempotent())->resolveStorageService($connection);

        $this->assertInstanceOf(RedisStorage::class, $service);
    }

    /**
     * @test
     */
    public function assert_storage_with_mysql_connection(): void
    {
        $connection = 'mysql';
        $service = (new Idempotent())->resolveStorageService($connection);

        $this->assertInstanceOf(MysqlStorage::class, $service);
    }

    /**
     * @test
     */
    public function assert_storage_with_redis_connection_throw_exception_with_wrong_password(): void
    {
        $connection = 'redis';
        config()->set('idempotent.redis.password', 'incorrect-password');
        $this->expectException(\RedisException::class);
        $this->expectExceptionMessage("Redis server went away");

        (new Idempotent())->resolveStorageService($connection);
    }
}
