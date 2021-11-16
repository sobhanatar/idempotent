<?php

namespace Sobhanatar\Idempotent\Contracts;

use Redis;
use malkusch\lock\mutex\PHPRedisMutex;
use Symfony\Component\HttpFoundation\Response;

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
    public function verify(string $entity, array $config, string $hash): array
    {
        $mutex = new PHPRedisMutex([$this->redis], 'idempotent');
        return $mutex->synchronized(function () use ($entity, $hash, $config) {
            $key = $this->getKey($entity, $hash);
            $result = $this->redis->get($key);
            if ($result) {
                return [true, json_decode($result, true, 512, JSON_THROW_ON_ERROR)];
            }

            $this->redis->set(
                $key,
                json_encode(['status' => 'progress', 'response' => ''], JSON_THROW_ON_ERROR),
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

            $code = $response->getStatusCode();
            $status = $code >= Response::HTTP_OK && $code <= Response::HTTP_IM_USED ? Storage::DONE : Storage::FAIL;

            $data = ['status' => $status, 'response' => serialize($response->getContent()), 'code' => $code];
            $this->redis->set($key, json_encode($data, JSON_THROW_ON_ERROR));
        });
    }

    /**
     * Get redis key
     *
     * @param string $entity
     * @param string $hash
     * @return string
     */
    public function getKey(string $entity, string $hash): string
    {
        return sprintf("%s-%s", $entity, $hash);
    }
}
