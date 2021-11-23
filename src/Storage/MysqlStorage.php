<?php

namespace Sobhanatar\Idempotent\Storage;

use PDO;
use malkusch\lock\mutex\MySQLMutex;
use Sobhanatar\Idempotent\StorageService;
use Symfony\Component\HttpFoundation\Response;

class MysqlStorage implements Storage
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
     * @param string $table
     */
    public function __construct(PDO $pdo, string $table)
    {
        $this->pdo = $pdo;
        $this->table = $table;
    }

    /**
     * @inheritDoc
     */
    public function verify(string $entity, array $config, string $hash): array
    {
        $lock = new MySQLMutex($this->pdo, $entity, $config['timeout']);
        return $lock->synchronized(function () use ($entity, $config, $hash): array {
            [$exist, $result] = $this->get($entity, $hash);
            if ($exist) {
                return [true, $result];
            }

            return $this->set($entity, $hash, (int)$config['ttl']);
        });
    }

    /**
     * @inheritDoc
     */
    public function update($response, string $entity, string $hash): void
    {
        $sql = sprintf(
            "%s %s %s",
            "UPDATE {$this->table}",
            "SET status=:status, response=:response, code=:code, updated_ut=:updated_ut, updated_at=:updated_at",
            "WHERE entity=:entity and hash=:hash"
        );

        $now = now();
        $code = $response->getStatusCode();
        $data = [
            'response' => serialize($response->getContent()),
            'status' => $code >= Response::HTTP_OK && $code <= Response::HTTP_IM_USED
                ? StorageService::DONE
                : StorageService::FAIL,
            'code' => $code,
            'entity' => $entity,
            'hash' => $hash,
            'updated_ut' => $now->unix(),
            'updated_at' => $now->format('Y-m-d H:i:s'),
        ];

        $this->pdo->prepare($sql)->execute($data);
    }

    /**
     * Check if the hash is exists for the entity within its ttl
     *
     * @param string $entity
     * @param string $hash
     * @return array
     */
    public function get(string $entity, string $hash): array
    {
        $sql = sprintf(
            "SELECT * FROM %s WHERE `entity` = '%s' AND `expired_ut` > %d AND `hash` = '%s' ORDER BY `id` DESC LIMIT 1",
            $this->table,
            $entity,
            now()->unix(),
            $hash,
        );

        $result = $this->pdo->query($sql)->fetch();
        if (isset($result['id'])) {
            return [true, $result];
        }

        return [false, []];
    }

    /**
     * Insert hash for the entity with its ttl
     *
     * @param string $entity
     * @param string $hash
     * @param int $ttl
     * @return array
     */
    public function set(string $entity, string $hash, int $ttl): array
    {
        $sql = sprintf(
            "%s %s",
            "INSERT INTO {$this->table} (entity, hash, status, expired_ut, created_ut, created_at, updated_ut, updated_at)",
            "VALUES (:entity, :hash, :status, :expired_ut, :created_ut, :created_at, :updated_ut, :updated_at)");
        $now = now();
        $this->pdo->prepare($sql)->execute([
            'entity' => $entity,
            'hash' => $hash,
            'status' => StorageService::PROGRESS,
            'expired_ut' => $now->unix() + $ttl,
            'created_ut' => $now->unix(),
            'created_at' => $now->format('Y-m-d H:i:s'),
            'updated_ut' => $now->unix(),
            'updated_at' => $now->format('Y-m-d H:i:s'),
        ]);

        return [false, []];
    }
}
