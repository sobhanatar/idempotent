<?php

namespace Sobhanatar\Idempotent;

use Redis;
use Exception;
use InvalidArgumentException;
use Illuminate\Support\Facades\DB;
use Sobhanatar\Idempotent\Contracts\{Storage, RedisStorage, MysqlStorage};

class Idempotent
{
    public const SEPARATOR = '_';

    /**
     * Get the required storage based on entity connection
     *
     * @throws InvalidArgumentException
     */
    public function resolveStorageService(string $connection): Storage
    {
        switch ($connection) {
            case Storage::MYSQL:
                return new MysqlStorage(DB::connection(Storage::MYSQL)->getPdo(), config('idempotent.table'));
            case Storage::REDIS:
                $redis = new Redis();
                if (config('idempotent.redis.password')) {
                    $redis->auth(config('idempotent.redis.password'));
                }
                $redis->connect(
                    config('idempotent.redis.host'),
                    config('idempotent.redis.port'),
                    config('idempotent.redis.timeout'),
                    config('idempotent.redis.reserved'),
                    config('idempotent.redis.retryInterval'),
                    config('idempotent.redis.readTimeout'),
                );
                return new RedisStorage($redis);
            default:
                throw new InvalidArgumentException(sprintf('connection `%s` is not supported', $connection));
        }
    }
//
//    /**
//     * Create Idempotent signature based on fields and headers
//     *
//     * @param array $requestBag
//     * @param string $entity
//     * @param array $config
//     * @return string
//     */
//    public function getSignature(array $requestBag, string $entity, array $config): string
//    {
//        return $this->makeSignature($requestBag, $entity, $config);
//    }
//
//    /**
//     * Create hash from the request signature
//     *
//     * @param string $key
//     * @return string
//     */
//    public function hash(string $key): string
//    {
//        return hash(config('idempotent.driver', 'sha256'), $key);
//    }

    /**
     * Set data into shared memory
     *
     * @param Storage $storage
     * @param string $entityName
     * @param array $entityConfig
     * @param string $hash
     * @return array
     * @throws Exception
     */
    public function verify(Storage $storage, string $entityName, array $entityConfig, string $hash): array
    {
        return $storage->verify($entityName, $entityConfig, $hash);
    }

    /**
     * Update data of shared storage
     *
     * @param Storage $storage
     * @param $response
     * @param string $entityName
     * @param string $hash
     * @return void
     * @throws Exception
     */
    public function update(Storage $storage, $response, string $entityName, string $hash): void
    {
        $storage->update($response, $entityName, $hash);
    }

    /**
     * Prepare response
     *
     * @param string $entity
     * @param string|null $response
     * @return string
     */
    public function prepareResponse(string $entity, ?string $response): string
    {
        return unserialize($response) ?? trans('idempotent.' . $entity);
    }
}
