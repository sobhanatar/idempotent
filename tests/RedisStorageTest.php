<?php

namespace Sobhanatar\Idempotent\Tests;

use Redis;
use Exception;
use Spatie\Async\Pool;
use Sobhanatar\Idempotent\Idempotent;
use Sobhanatar\Idempotent\Contracts\{RedisStorage, Storage};

class RedisStorageTest extends TestCase
{
    /**
     * @var int
     */
    public int $counter = 0;

    /**
     * @test
     */
    public function assert_redis_node_exists(): void
    {
        $this->assertTrue($this->getRedisConnection());
    }

    /**
     * @test
     */
    public function assert_service_has_required_extension_for_redis(): void
    {
        $this->assertTrue((bool)phpversion('redis'));
    }

    /**
     * @test
     */
    public function assert_multiple_request_create_single_hash(): void
    {
        $i = 0;
        $config = [
            'processes' => 10,
            'entity' => 'request',
            'hash' => 'some hash',
            'ttl' => 100,
            'host' => config('idempotent.redis.host'),
            'port' => config('idempotent.redis.port'),
            'password' => config('idempotent.redis.password'),
            'timeout' => config('idempotent.redis.timeout'),
            'reserved' => config('idempotent.redis.reserved'),
            'retryInterval' => config('idempotent.redis.retryInterval'),
            'readTimeout' => config('idempotent.redis.readTimeout'),
        ];

        $redisMock = $this->getMockBuilder(Redis::class)->getMock();
        $redisInstance = new RedisStorage($redisMock);
        $key = $redisInstance->getKey($config['entity'], $config['hash']);

        $this->getRedisConnection($config);
        $this->redis->del($key);

        $pool = Pool::create();
        for (; $i < $config['processes']; $i++) {
            $pool->add(function () use ($config) {
                $redis = new Redis();
                if ($config['password']) {
                    $redis->auth($config['password']);
                }
                $redis->connect(
                    $config['host'],
                    $config['port'],
                    $config['timeout'],
                    $config['reserved'],
                    $config['retryInterval'],
                    $config['readTimeout'],
                );

                $service = new RedisStorage($redis);
                return (new Idempotent())->verify($service, $config['entity'], $config, $config['hash']);

            })->then(function ($output) {
                if (!$output[0]) {
                    $this->counter++;
                }
            });
        }
        $pool->wait();

        $res = json_decode($this->redis->get($key), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals(1, $this->counter);
        $this->assertArrayHasKey('status', $res);
        $this->assertArrayHasKey('response', $res);
        $this->assertEquals(Storage::PROGRESS, $res['status']);
        $this->assertEquals($config['processes'], $i);
        $this->redis->del($key);
    }

    /**
     * @test
     * @throws Exception
     */
    public function assert_multiple_request_create_new_hash_after_ttl(): void
    {
        $config = [
            'entity' => 'request',
            'hash' => 'some hash',
            'ttl' => 1,
            'host' => config('idempotent.redis.host'),
            'port' => config('idempotent.redis.port'),
            'password' => config('idempotent.redis.password'),
            'timeout' => config('idempotent.redis.timeout'),
            'reserved' => config('idempotent.redis.reserved'),
            'retryInterval' => config('idempotent.redis.retryInterval'),
            'readTimeout' => config('idempotent.redis.readTimeout'),
        ];
        $redisMock = $this->getMockBuilder(Redis::class)->getMock();
        $redisInstance = new RedisStorage($redisMock);
        $key = $redisInstance->getKey($config['entity'], $config['hash']);

        $this->getRedisConnection($config);
        $this->redis->del($key);

        $service = new RedisStorage($this->redis);
        $result = (new Idempotent())->verify($service, $config['entity'], $config, $config['hash']);
        $resArray = json_decode($this->redis->get($key), true, 512, JSON_THROW_ON_ERROR);

        $this->assertFalse($result[0]);
        $this->assertArrayHasKey('status', $resArray);
        $this->assertArrayHasKey('response', $resArray);
        $this->assertEquals(Storage::PROGRESS, $resArray['status']);
        sleep($config['ttl'] + 1);

        $result = (new Idempotent())->verify($service, $config['entity'], $config, $config['hash']);
        $resArray = json_decode($this->redis->get($key), true, 512, JSON_THROW_ON_ERROR);

        $this->assertFalse($result[0]);
        $this->assertArrayHasKey('status', $resArray);
        $this->assertArrayHasKey('response', $resArray);
        $this->assertEquals(Storage::PROGRESS, $resArray['status']);

        $this->redis->del($key);
    }

    /**
     * @test
     */
    public function assert_multiple_request_create_multiple_hash_with_different_entity(): void
    {
        $config = [
            'processes' => 5,
            'hash' => 'some hash',
            'ttl' => 15,
            'host' => config('idempotent.redis.host'),
            'port' => config('idempotent.redis.port'),
            'password' => config('idempotent.redis.password'),
            'timeout' => config('idempotent.redis.timeout'),
            'reserved' => config('idempotent.redis.reserved'),
            'retryInterval' => config('idempotent.redis.retryInterval'),
            'readTimeout' => config('idempotent.redis.readTimeout'),
        ];
        $redisMock = $this->getMockBuilder(Redis::class)->getMock();
        $redisInstance = new RedisStorage($redisMock);

        for ($i = 0; $i < $config['processes']; $i++) {
            $config['entities'][] = sprintf('request_%d', $i);
            $config['keys'][] = $redisInstance->getKey($config['entities'][$i], $config['hash']);
        }

        $this->getRedisConnection($config);
        $this->redis->del($config['keys']);

        $pool = Pool::create();
        for ($i = 0; $i < $config['processes']; $i++) {
            $pool->add(function () use ($i, $config) {
                $redis = new Redis();
                if ($config['password']) {
                    $redis->auth($config['password']);
                }
                $redis->connect(
                    $config['host'],
                    $config['port'],
                    $config['timeout'],
                    $config['reserved'],
                    $config['retryInterval'],
                    $config['readTimeout'],
                );

                $service = new RedisStorage($redis);
                return (new Idempotent())->verify($service, $config['entities'][$i], $config, $config['hash']);

            })->then(function ($output) {
                if (!$output[0]) {
                    $this->counter++;
                }
            });
        }
        $pool->wait();

        $this->assertEquals($config['processes'], $this->counter);
        $this->assertTrue((bool)$this->redis->exists($config['keys'][0]));
        $this->redis->del($config['keys']);
    }
}
