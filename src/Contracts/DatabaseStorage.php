<?php

namespace Sobhanatar\Idempotent\Contracts;

use Illuminate\Support\Facades\DB;

class DatabaseStorage implements StorageInterface
{
    /**
     * @inheritDoc
     */
    public function set(string $entity, array $config, string $hash)
    {
        $connection = config('idempotent.storage.database.connection');
//        DB::connection($connection)->table('test')->count();
        dump(DB::connection($connection)->table('test')->count());
    }

    /**
     * @inheritDoc
     */
    public function update(array $data)
    {
        // TODO: Implement update() method.
    }
}
