<?php

namespace Sobhanatar\Idempotent\Tests;

use Illuminate\Support\Facades\DB;
use Sobhanatar\Idempotent\Contracts\MysqlStorage;
use Sobhanatar\Idempotent\Idempotent;

class SignatureTest extends TestCase
{
    private array $requestBag = [
        'fields' => ['name' => 'idempotent', 'surname' => 'package'],
        'headers' => ['host' => '127.0.0.1', 'user-agent' => 'test'],
        'servers' => ['REMOTE_ADDR' => '127.0.0.2'],
    ];

    private string $entity = 'news';

    /**
     * @test
     */
    public function assert_make_signature(): void
    {
        $config = [
            'fields' => ['name', 'surname']
        ];

        $signature = (new Idempotent())->getSignature($this->requestBag, $this->entity, $config);
        $this->assertIsString($signature);

        $signature = explode(Idempotent::SEPARATOR, $signature);

        $this->assertIsArray($signature);
        $this->assertContains('news', $signature);
        $this->assertContains('idempotent', $signature);
        $this->assertContains('package', $signature);
    }

    /**
     * @test
     */
    public function assert_make_signature_with_header(): void
    {
        $config = [
            'fields' => ['name', 'surname'],
            'headers' => ['Host', 'User-Agent']
        ];

        $signature = (new Idempotent())->getSignature($this->requestBag, $this->entity, $config);
        $signature = explode(Idempotent::SEPARATOR, $signature);

        $this->assertIsArray($signature);
        $this->assertContains('news', $signature);
        $this->assertContains('idempotent', $signature);
        $this->assertContains('package', $signature);
        $this->assertContains('127.0.0.1', $signature);
        $this->assertContains('test', $signature);
    }

    /**
     * @test
     */
    public function assert_make_signature_with_servers(): void
    {
        $config = [
            'fields' => ['name', 'surname'],
            'servers' => ['REMOTE_ADDR']
        ];

        $signature = (new Idempotent())->getSignature($this->requestBag, $this->entity, $config);
        $signature = explode(Idempotent::SEPARATOR, $signature);

        $this->assertIsArray($signature);
        $this->assertContains('news', $signature);
        $this->assertContains('idempotent', $signature);
        $this->assertContains('package', $signature);
        $this->assertContains('127.0.0.2', $signature);
    }

    /**
     * @test
     */
    public function assert_make_signature_with_headers_and_servers(): void
    {
        $config = [
            'fields' => ['name', 'surname'],
            'headers' => ['Host', 'User-Agent'],
            'servers' => ['REMOTE_ADDR']
        ];

        $signature = (new Idempotent())->getSignature($this->requestBag, $this->entity, $config);
        $signature = explode(Idempotent::SEPARATOR, $signature);

        $this->assertIsArray($signature);
        $this->assertContains('news', $signature);
        $this->assertContains('idempotent', $signature);
        $this->assertContains('package', $signature);
        $this->assertContains('127.0.0.1', $signature);
        $this->assertContains('test', $signature);
        $this->assertContains('127.0.0.2', $signature);
    }

    /**
     * @test
     */
    public function assert_make_signature_with_multiple_values_in_headers(): void
    {
        $requestBag = [
            'fields' => ['name' => 'idempotent', 'surname' => 'package'],
            'headers' => ['Host' => ['127.0.0.1', '10.0.0.1']],
            'servers' => ['REMOTE_ADDR' => '127.0.0.2'],
        ];

        $config = [
            'fields' => ['name', 'surname'],
            'headers' => ['Host', 'User-Agent'],
        ];

        $signature = (new Idempotent())->getSignature($requestBag, $this->entity, $config);
        $signature = explode(Idempotent::SEPARATOR, $signature);

        $this->assertIsArray($signature);
        $this->assertContains('news', $signature);
        $this->assertContains('idempotent', $signature);
        $this->assertContains('package', $signature);
        $this->assertContains('127.0.0.1', $signature);
        $this->assertContains('10.0.0.1', $signature);
    }

    /**
     * @test
     */
    public function assert_make_signature_with_multiple_values_in_server(): void
    {
        $requestBag = [
            'fields' => ['name' => 'idempotent', 'surname' => 'package'],
            'headers' => ['Host' => '127.0.0.1'],
            'servers' => ['REMOTE_ADDR' => ['127.0.0.2', '10.0.0.2']],
        ];

        $config = [
            'fields' => ['name', 'surname'],
            'servers' => ['REMOTE_ADDR'],
        ];

        $signature = (new Idempotent())->getSignature($requestBag, $this->entity, $config);
        $signature = explode(Idempotent::SEPARATOR, $signature);

        $this->assertIsArray($signature);
        $this->assertContains('news', $signature);
        $this->assertContains('idempotent', $signature);
        $this->assertContains('package', $signature);
        $this->assertContains('127.0.0.2', $signature);
        $this->assertContains('10.0.0.2', $signature);
    }

    /**
     * @test
     */
    public function assert_make_signature_works_with_mix_lowercase_uppercase_headers_servers_params(): void
    {
        $requestBag = [
            'fields' => ['name' => 'idempotent', 'surname' => 'package'],
            'headers' => ['HOst' => '127.0.0.1', 'usER-agENt' => 'test'],
            'servers' => ['ReMOTe_addR' => '127.0.0.2'],
        ];

        $config = [
            'fields' => ['name', 'surname'],
            'headers' => ['hoST', 'USer-AgENt'],
            'servers' => ['rEMoTE_AddR']
        ];

        $signature = (new Idempotent())->getSignature($requestBag, $this->entity, $config);
        $signature = explode(Idempotent::SEPARATOR, $signature);

        $this->assertIsArray($signature);
        $this->assertContains('news', $signature);
        $this->assertContains('idempotent', $signature);
        $this->assertContains('package', $signature);
        $this->assertContains('127.0.0.1', $signature);
        $this->assertContains('test', $signature);
        $this->assertContains('127.0.0.2', $signature);
    }
}
