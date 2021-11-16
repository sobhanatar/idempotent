<?php

namespace Sobhanatar\Idempotent\Tests;

use Exception;
use Spatie\Async\Pool;
use Sobhanatar\Idempotent\Idempotent;
use Illuminate\Support\Facades\{DB, Schema};
use Sobhanatar\Idempotent\Contracts\{Storage, MysqlStorage};

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
    public function assert_multiple_request_create_single_hash(): void
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
        $this->assertDatabaseHas(
            $config['table'],
            [
                'hash' => 'some hash',
                'entity' => 'request',
                'status' => Storage::PROGRESS
            ],
            'mysql');
        $this->assertDatabaseCount($config['table'], 1, 'mysql');
    }

    /**
     * @test
     * @throws Exception
     */
    public function assert_multiple_request_create_new_hash_after_ttl(): void
    {
        $config = [
            'timeout' => 10,
            'ttl' => 1,
            'table' => config('idempotent.table')
        ];
        $this->loadMigrationsFrom(self::MIGRATION_PATH);
        $service = new MysqlStorage(DB::connection('mysql')->getPDO(), $config['table']);
        $res = (new Idempotent())->verify($service, 'request', $config, 'some hash');

        $this->assertFalse($res[0]);
        $this->assertDatabaseHas(
            $config['table'],
            [
                'hash' => 'some hash',
                'entity' => 'request',
                'status' => Storage::PROGRESS
            ],
            'mysql');
        $this->assertDatabaseCount($config['table'], 1, 'mysql');
        sleep($config['ttl'] + 1);

        $res = (new Idempotent())->verify($service, 'request', $config, 'some hash');

        $this->assertFalse($res[0]);
        $this->assertDatabaseHas(
            $config['table'],
            [
                'hash' => 'some hash',
                'entity' => 'request',
                'status' => Storage::PROGRESS
            ],
            'mysql');
        $this->assertDatabaseCount($config['table'], 2, 'mysql');
    }


    /**
     * @test
     */
    public function assert_multiple_request_create_multiple_hash_with_different_entity(): void
    {
        $i = 0;
        $config = [
            'processes' => 5,
            'timeout' => 10,
            'ttl' => 100,
            'host' => env('DB_HOST'),
            'port' => env('DB_PORT'),
            'db' => env('DB_DATABASE'),
            'table' => config('idempotent.table'),
            'user' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
        ];

        for ($i = 0; $i < $config['processes']; $i++) {
            $config['entities'][$i] = sprintf('request_%d', $i);
        }

        $this->loadMigrationsFrom(self::MIGRATION_PATH);

        $pool = Pool::create();
        for ($i = 0; $i < $config['processes']; $i++) {
            $pool->add(function () use ($i, $config) {
                $pdo = new \PDO(
                    sprintf('mysql:host=%s;port=%d;dbname=%s', $config['host'], $config['port'], $config['db']),
                    $config['user'],
                    $config['password']
                );
                $service = new MysqlStorage($pdo, $config['table']);
                return (new Idempotent())->verify($service, $config['entities'][$i], $config, 'some hash');

            })->then(function ($output) {
                if (!$output[0]) {
                    $this->counter++;
                }
            })->catch(function (Exception $e) {
                dump($e);
            });
        }
        $pool->wait();
        $this->assertEquals($config['processes'], $this->counter);
        $this->assertEquals($config['processes'], $i);
        $this->assertDatabaseCount($config['table'], $config['processes'], 'mysql');
    }
}
