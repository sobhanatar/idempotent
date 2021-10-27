<?php

namespace Sobhanatar\Idempotent\Contracts;

use Illuminate\Support\Facades\Redis;
use Psr\SimpleCache\InvalidArgumentException;

class RedisStorage implements StorageInterface
{
    /**
     * @inheritDoc
     */
    public function set(string $entity, array $config, string $hash)
    {
        Redis::transaction(function ($redis) {
            $redis->incr('user_visits', 1);
            $redis->incr('total_visits', 1);
        });
    }

    /**
     * @inheritDoc
     */
    public function update(array $data)
    {
        // TODO: Implement update() method.
    }
}
