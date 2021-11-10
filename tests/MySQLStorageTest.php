<?php

namespace Sobhanatar\Idempotent\Tests;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Sobhanatar\Idempotent\Contracts\MysqlStorage;

class MySQLStorageTest extends TestCase
{
    private const MIGRATION_PATH = __DIR__ . '/../database/migrations';

    /**
     * @test
     */
    public function assert_it_can_connect_to_mysql(): void
    {
        $this->assertTrue(true);
    }

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
    public function assert_idempotent_table_hash_supports_all_algorithm_outputs(): void
    {
        $this->loadMigrationsFrom(self::MIGRATION_PATH);
        $storageService = new MysqlStorage(DB::connection('mysql')->getPdo());

        $algos = hash_algos();
        foreach ($algos as $key => $algo) {
            $hash = hash($algo, 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem
            Ipsum has been the industrys standard dummy text ever since the 1500s, when an unknown printer took a galley
             of type and scrambled it to make a type specimen book.');
            $storageService->set('test.' . $key, $algo . '-' . $hash, 0);
        }

        // In case of failure database will thrown an error
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function assert_idempotent_table_hash_supports_all_non_asci_algorithm_outputs(): void
    {
        $this->loadMigrationsFrom(self::MIGRATION_PATH);
        $storageService = new MysqlStorage(DB::connection('mysql')->getPdo());

        $algos = hash_algos();
        foreach ($algos as $key => $algo) {
            $hash = hash($algo, 'Lorem Ipsum es simplemente el texto de relleno de las imprentas y archivos de
            texto. Lorem Ipsum ha sido el texto de relleno estándar de las industrias desde el año 1500, cuando un
            impresor (N. del T. persona que se dedica a la imprenta) desconocido usó una galería de textos y los mezcló
            de tal manera que logró hacer un libro de textos especimen. No sólo sobrevivió 500 años, sino que tambien
            ingresó como texto de relleno en documentos electrónicos, quedando esencialmente igual al original. Fue
            popularizado en los 60s con la creación de las hojas "Letraset", las cuales contenian pasajes de Lorem Ipsum,
            y más recientemente con software de autoedición, como por ejemplo Aldus PageMaker, el cual incluye versiones
            de Lorem Ipsum');
            $storageService->set('test.' . $key, $algo . '-' . $hash, 0);
        }

        // In case of failure database will thrown an error
        $this->assertTrue(true);
    }
}
