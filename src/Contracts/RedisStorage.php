<?php

namespace Sobhanatar\Idempotent\Contracts;

use Illuminate\Support\Facades\Redis;
use Psr\SimpleCache\InvalidArgumentException;

class RedisStorage implements StorageInterface
{
    /**
     * @inheritDoc
     */
    public function set(string $entity, array $config, string $hash): array
    {
        Redis::transaction(function ($redis) {
            $redis->incr('user_visits', 1);
            $redis->incr('total_visits', 1);
        });

        $value = Redis::eval(<<<'LUA'
    local counter = redis.call("incr", KEYS[1])

    if counter > 5 then
        redis.call("incr", KEYS[2])
    end

    return counter
LUA, 2, 'first-counter', 'second-counter');
    }

    /**
     * @inheritDoc
     */
    public function update(Response $response)
    {
        // TODO: Implement update() method.
    }
}
