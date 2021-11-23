<?php

namespace Sobhanatar\Idempotent;

use Redis;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Sobhanatar\Idempotent\Storage\{Storage, MysqlStorage, RedisStorage};

class StorageService
{
    public const MYSQL = 'mysql';
    public const REDIS = 'redis';
    public const DONE = 'done';
    public const FAIL = 'fail';
    public const PROGRESS = 'progress';

    private bool $exists;
    private Storage $strategy;
    private $response;

    /**
     * Get storage strategy
     *
     * @param Config $config
     * @return $this
     * @throws Exception
     */
    public function resolveStrategy(Config $config): StorageService
    {
        switch ($config->getStorage()) {
            case self::MYSQL:
                $this->setStrategy($this->getMySqlConnection($config->getTable()));
                break;
            case self::REDIS:
                $this->setStrategy($this->getRedisConnection($config->getRedis()));
                break;
            default:
                throw new Exception(sprintf('connection `%s` is not supported', $config->getStorage()));
        }

        return $this;
    }

    /**
     * @param Storage $strategy
     */
    public function setStrategy(Storage $strategy): void
    {
        $this->strategy = $strategy;
    }

    /**
     * @return Storage
     */
    public function getStrategy(): Storage
    {
        return $this->strategy;
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        return $this->exists;
    }

    /**
     * @param bool $exists
     */
    public function setExists(bool $exists): void
    {
        $this->exists = $exists;
    }

    /**
     * @param array $response
     */
    public function setResponse(array $response): void
    {
        $this->response = $response;
    }

    /**
     * @return array
     */
    public function getResponse(): array
    {
        return $this->response;
    }

    /**
     * Verify the idempotent signature
     *
     * @param string $entity
     * @param array $config
     * @param string $hash
     * @return $this
     * @throws Exception
     */
    public function verify(string $entity, array $config, string $hash): StorageService
    {
        $result = $this->strategy->verify($entity, $config, $hash);
        $this->setExists($result[0]);
        $this->setResponse($result[1]);
        return $this;
    }

    /**
     * Update the idempotent signature
     *
     * @param $response
     * @param string $entity
     * @param string $hash
     * @return $this
     * @throws Exception
     */
    public function update($response, string $entity, string $hash): StorageService
    {
        $this->strategy->update($response, $entity, $hash);
        return $this;
    }

    /**
     * Get redis connection
     *
     * @param array $config
     * @return RedisStorage
     */
    private function getRedisConnection(array $config): RedisStorage
    {
        $redis = new Redis();
        if ($config['password']) {
            $redis->auth(config('idempotent.redis.password'));
        }
        $redis->connect(
            $config['host'],
            $config['port'],
            $config['timeout'],
            $config['reserved'],
            $config['retryInterval'],
            $config['readTimeout'],
        );

        return new RedisStorage($redis);
    }

    /**
     * Get MySQL connection
     *
     * @param string $table
     * @return MysqlStorage
     */
    protected function getMySqlConnection(string $table): MysqlStorage
    {
        return new MysqlStorage(DB::connection(self::MYSQL)->getPdo(), $table);
    }
}