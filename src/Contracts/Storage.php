<?php

namespace Sobhanatar\Idempotent\Contracts;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use malkusch\lock\exception\LockAcquireException;
use malkusch\lock\exception\LockReleaseException;
use malkusch\lock\exception\ExecutionOutsideLockException;

interface Storage
{
    /**
     * Check if hash existence, and set non-existent hash in storage
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
    public function verify(string $entity, array $config, string $hash): array;

    /**
     * Update hash in storage
     *
     * @param Response|JsonResponse $response
     * @param string $entity
     * @param string $hash
     * @return void
     * @throws Exception
     */
    public function update($response, string $entity, string $hash): void;
}
