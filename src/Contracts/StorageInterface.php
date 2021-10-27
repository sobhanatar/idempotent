<?php

namespace Sobhanatar\Idempotent\Contracts;

interface StorageInterface
{
    /**
     * Set hash to storage
     *
     * @param string $entity
     * @param array $config
     * @param string $hash
     * @return mixed
     */
    public function set(string $entity, array $config, string $hash);

    /**
     * Update hash in storage
     *
     * @param array $data
     * @return mixed
     */
    public function update(array $data);
}
