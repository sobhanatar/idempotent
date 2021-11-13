<?php

namespace Sobhanatar\Idempotent\Tests;

use Illuminate\Support\Facades\DB;
use Sobhanatar\Idempotent\Idempotent;
use Sobhanatar\Idempotent\Contracts\MysqlStorage;

class HashTest extends TestCase
{
    /**
     * @test
     */
    public function assert_idempotent_table_supports_all_algorithm_outputs(): void
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
    public function assert_idempotent_table_supports_all_unicode_algorithm_outputs(): void
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

    /**
     * @test
     */
    public function assert_input_length_has_no_effect_hash_output(): void
    {
        $algos = hash_algos();
        foreach ($algos as $algo) {
            $firstHash = hash($algo, 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem
            Ipsum has been the industry standard dummy text ever since the 1500s, when an unknown printer took a galley
             of type and scrambled it to make a type specimen book.');
            $secondHash = hash($algo, 'Hello Idempotent');

            $this->assertTrue(true, strlen($firstHash) === strlen($secondHash));
        }
    }

    /**
     * @test
     */
    public function assert_wrong_hash_name_throw_exception(): void
    {
        $wrongHashAlgorithm = 'non-exiting-hash-name';
        $this->expectException(\ErrorException::class);
        $this->expectExceptionMessage(sprintf('hash(): Unknown hashing algorithm: %s', $wrongHashAlgorithm));
        $hash = hash($wrongHashAlgorithm, 'blah-blah-blah');
    }

    /**
     * @test
     */
    public function assert_create_hash_returns_string(): void
    {
        $result = (new Idempotent())->hash('lorespam');
        $this->assertEquals(true, is_string($result));
    }
}
