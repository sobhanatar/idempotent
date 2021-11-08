<?php

namespace Sobhanatar\Idempotent\Contracts;

use Redis;
use malkusch\lock\mutex\PHPRedisMutex;
use Symfony\Component\HttpFoundation\Response as SymphonyResponse;

class RedisStorage implements Storage
{
    /**
     * @var Redis $redis
     */
    private Redis $redis;

    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * @inheritDoc
     */
    public function set(string $entity, array $config, string $hash): array
    {
        $mutex = new PHPRedisMutex([$this->redis], 'idempotent');
        return $mutex->synchronized(function () use ($entity, $hash, $config) {
            $key = $this->getKey($entity, $hash);
            $result = $this->redis->get($key);
            if ($result) {
                return [true, unserialize($result, ['allowed_classes' => true])];
            }

            $this->redis->set(
                $this->getKey($entity, $hash),
                serialize(collect(['status' => 'progress', 'response' => ''])),
                $config['ttl']
            );
            return [false, null];
        });
    }

    /**
     * @inheritDoc
     */
    public function update($response, string $entity, string $hash): void
    {
        $mutex = new PHPRedisMutex([$this->redis], 'idempotent');
        $mutex->synchronized(function () use ($response, $entity, $hash) {
            $key = $this->getKey($entity, $hash);
            $result = $this->redis->exists($key);
            if (!$result) {
                return;
            }

            $status = 'fail';
            $statusCode = $response->getStatusCode();
            if ($statusCode >= SymphonyResponse::HTTP_OK && $statusCode <= SymphonyResponse::HTTP_IM_USED) {
                $status = 'done';
            }

            $data = ['status' => $status, 'response' => $response->getContent()];
            $this->redis->set($key, serialize(collect($data)));
        });
    }

    /**
     * Get redis key
     *
     * @param string $entity
     * @param string $hash
     * @return string
     */
    private function getKey(string $entity, string $hash): string
    {
        return sprintf("%s-%s", $entity, $hash);
    }
}
