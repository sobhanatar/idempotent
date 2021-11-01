<?php

namespace Sobhanatar\Idempotent\Contracts;

use Exception;
use malkusch\lock\exception\LockAcquireException;
use malkusch\lock\exception\LockReleaseException;
use malkusch\lock\exception\ExecutionOutsideLockException;

interface StorageInterface
{
    /**
     * Set hash to storage and return if hash exists
     *
     * @param string $entity
     * @param array $config
     * @param string $hash
     * @return mixed
     * @throws LockAcquireException The mutex could not
     * be acquired, no further side effects.
     * @throws LockReleaseException The mutex could not
     * be released, the code was already executed.
     * @throws ExecutionOutsideLockException Some code
     * has been executed outside of the lock.
     * @throws Exception The execution callback threw an exception.
     */
    public function set(string $entity, array $config, string $hash): array;

    /**
     * Update hash in storage
     *
     * @param array $data
     * @return mixed
     */
    public function update(array $data);
}
