<?php

namespace Sobhanatar\Idempotent\Contracts;

use PDO;
use Exception;
use malkusch\lock\mutex\MySQLMutex;

class MysqlStorage implements StorageInterface
{
    /**
     * @var PDO $pdo
     */
    private PDO $pdo;

    /**
     * @var string $table
     */
    private string $table;

    /**
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->table = config('idempotent.database.table');
    }

    /**
     * @inheritDoc
     */
    public function set(string $entity, array $config, string $hash): array
    {
        $lock = new MySQLMutex($this->pdo, $entity, $config['timeout']);
        return $lock->synchronized(function () use ($entity, $config, $hash): array {
            [$exist, $result] = $this->check($entity, $hash);
            if ($exist) {
                return [true, $result];
            }

            return $this->insert($entity, $hash, (int)$config['ttl']);
        });
    }

    /**
     * @inheritDoc
     */
    public function update(array $data)
    {
        // TODO: Implement update() method.
    }

    /**
     * Check if the hash is exists for the entity within its ttl
     *
     * @param string $entity
     * @param string $hash
     * @return array
     */
    private function check(string $entity, string $hash): array
    {
        $sql = sprintf(
            "SELECT `id`, `status`, `response` FROM %s WHERE `entity` = '%s' AND `hash` = '%s' AND `expired_ut` > %d ORDER BY `id` DESC LIMIT 1",
            $this->table,
            $entity,
            $hash,
            now()->unix()
        );

        $result = $this->pdo->query($sql)->fetch();
        if (isset($result['id']) && $result['id'] > 0) {
            return [true, $result];
        }

        return [false, null];
    }

    /**
     * Insert hash for the entity with its ttl
     *
     * @param string $entity
     * @param string $hash
     * @param int $ttl
     * @return array
     */
    private function insert(string $entity, string $hash, int $ttl): array
    {
        $sql = sprintf(
            "%s %s",
            "INSERT INTO {$this->table} (entity, hash, status, expired_ut, created_ut, created_at)",
            "VALUES (:entity, :hash, :status, :expired_ut, :created_ut, :created_at)");
        $now = now();
        $this->pdo->prepare($sql)->execute([
            'entity' => $entity,
            'hash' => $hash,
            'status' => 'progress',
            'expired_ut' => $now->unix() + $ttl,
            'created_ut' => $now->unix(),
            'created_at' => $now->format('Y-m-d H:i:s'),
        ]);

        return [false, null];
    }
}
