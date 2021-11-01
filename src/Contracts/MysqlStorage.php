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
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @inheritDoc
     */
    public function set(string $entity, array $config, string $hash): array
    {
        $lock = new MySQLMutex($this->pdo, $entity, $config['timeout']);
        return $lock->synchronized(function () use ($entity, $config, $hash): array {
            [$exist, $response] = $this->checkHash($entity, $hash, $config);
            if ($exist) {
                return $response ? [true, $response] : [true, 'default message'];
            }

            $sql = "INSERT INTO test (hash) VALUES (:hash)";
            $this->pdo->prepare($sql)->execute(['hash' => $hash]);
            return [false, null];
        });
    }

    /**
     * @inheritDoc
     */
    public function update(array $data)
    {
        // TODO: Implement update() method.
    }

    private function checkHash(string $entity, string $hash, array $config): array
    {
        $result = $this->pdo->query("SELECT * FROM test ORDER BY id DESC LIMIT 1")->fetch();
        if (isset($result['hash']) && trim($result['hash']) !== '') {
            return [true, $result['hash']];
        }

        return [false, null];
    }
}
