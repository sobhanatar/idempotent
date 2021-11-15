<?php

namespace Sobhanatar\Idempotent\Tests;

use Exception;
use Spatie\Async\Pool;
use Sobhanatar\Idempotent\Idempotent;
use Illuminate\Support\Facades\Schema;
use Sobhanatar\Idempotent\Contracts\MysqlStorage;

class MySQLStorageTest extends TestCase
{
    public \PDO $pdo;

    public int $counter = 0;

    /**
     * @test
     */
    public function assert_idempotent_table_exists(): void
    {
        $this->loadMigrationsFrom(self::MIGRATION_PATH);
        $this->assertTrue(Schema::hasTable(config('idempotent.table')));
    }

    /**
     * @test
     */
    public function assert_service_has_required_extension_for_async_test(): void
    {
        $this->assertTrue(Pool::isSupported());
    }

    /**
     * @test
     */
    public function assert_multiple_request_create_single_hash_in_database(): void
    {
        $i = 0;
        $config = [
            'processes' => 10,
            'timeout' => 10,
            'ttl' => 100,
            'host' => env('DB_HOST'),
            'port' => env('DB_PORT'),
            'db' => env('DB_DATABASE'),
            'table' => config('idempotent.table'),
            'user' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
        ];
        $this->loadMigrationsFrom(self::MIGRATION_PATH);

        $pool = Pool::create();
        for (; $i < $config['processes']; $i++) {
            $pool->add(function () use ($config) {
                $pdo = new \PDO(
                    sprintf('mysql:host=%s;port=%d;dbname=%s', $config['host'], $config['port'], $config['db']),
                    $config['user'],
                    $config['password']
                );
                $service = new MysqlStorage($pdo, $config['table']);
                return (new Idempotent())->verify($service, 'request', $config, 'some hash');

            })->then(function ($output) {
                if (!$output[0]) {
                    $this->counter++;
                }
            })->catch(function (Exception $e) {
                dump($e);
            });
        }
        $pool->wait();
        $this->assertEquals(1, $this->counter);
        $this->assertEquals($config['processes'], $i);
        $this->assertDatabaseHas($config['table'], ['hash' => 'some hash', 'entity' => 'request'], 'mysql');
        $this->assertDatabaseCount($config['table'], 1, 'mysql');
    }
}
