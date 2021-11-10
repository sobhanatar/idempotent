<?php

namespace Sobhanatar\Idempotent\Tests;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sobhanatar\Idempotent\Contracts\MysqlStorage;

class HashTest extends TestCase
{

    /**
     * @test
     */
    public function assert_different_input_length_does_not_affect_hash_outputs(): void
    {
        $algos = hash_algos();
        foreach ($algos as $algo) {
            $firstHash = hash($algo, 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem
            Ipsum has been the industrys standard dummy text ever since the 1500s, when an unknown printer took a galley
             of type and scrambled it to make a type specimen book.');
            $secondHash = hash($algo, 'Hello Idempotent');

            $this->assertTrue(true, strlen($firstHash) === strlen($secondHash));
        }
    }
}
