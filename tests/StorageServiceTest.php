<?php

namespace Sobhanatar\Idempotent\Tests;

use Exception;
use RedisException;
use Sobhanatar\Idempotent\{Config, StorageService};
use Sobhanatar\Idempotent\storage\{MysqlStorage, RedisStorage};

class StorageServiceTest extends TestCase
{
    /**
     * @test
     * @throws Exception
     */
    public function assert_storage_with_no_valid_connection_return_error(): void
    {
        $connection = 'invalid';
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(sprintf('connection `%s` is not supported', $connection));

        $this->getRequest();
        config()->set('idempotent.entities.news_post.storage', $connection);
        $this->config = (new Config())->resolveConfig($this->request);
        (new StorageService())->resolveStrategy($this->config);
    }

    /**
     * @test
     * @throws Exception
     */
    public function assert_storage_with_redis_connection(): void
    {
        $this->getRequest();
        config()->set('idempotent.entities.news_post.storage', StorageService::REDIS);
        $this->config = (new Config())->resolveConfig($this->request);
        $service = (new StorageService())->resolveStrategy($this->config);

        $this->assertInstanceOf(RedisStorage::class, $service->getStrategy());
    }

    /**
     * @test
     * @throws Exception
     */
    public function assert_storage_with_mysql_connection(): void
    {
        $this->getRequest();
        config()->set('idempotent.entities.news_post.storage', StorageService::MYSQL);
        $this->config = (new Config())->resolveConfig($this->request);
        $service = (new StorageService())->resolveStrategy($this->config);

        $this->assertInstanceOf(MysqlStorage::class, $service->getStrategy());
    }

    /**
     * @test
     * @throws Exception
     */
    public function assert_storage_with_redis_connection_throw_exception_with_wrong_password(): void
    {
        $this->expectException(RedisException::class);
        $this->expectExceptionMessage("Redis server went away");

        $this->getRequest();
        config()->set('idempotent.entities.news_post.storage', StorageService::REDIS);
        config()->set('idempotent.redis.password', 'incorrect-password');
        $this->config = (new Config())->resolveConfig($this->request);
        (new StorageService())->resolveStrategy($this->config);
    }
}
