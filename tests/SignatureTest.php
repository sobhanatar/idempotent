<?php

namespace Sobhanatar\Idempotent\Tests;

use Exception;
use Sobhanatar\Idempotent\Config;
use Sobhanatar\Idempotent\Signature;

class SignatureTest extends TestCase
{
    /**
     * @test
     * @throws Exception
     */
    public function assert_make_signature(): void
    {
        $this->getRequest();
        $this->config = (new Config())->resolveConfig($this->request);
        $signatureObj = (new Signature())->makeSignature($this->request, $this->config);

        $this->assertIsString($signatureObj->getSignature());

        $signature = explode(Signature::SIGNATURE_SEPARATOR, $signatureObj->getSignature());

        $this->assertIsArray($signature);
        $this->assertContains('news', $signature);
        $this->assertContains('post', $signature);
        $this->assertContains('title', $signature);
        $this->assertContains('summary', $signature);
    }

    /**
     * @test
     * @throws Exception
     */
    public function assert_make_signature_with_header(): void
    {
        $this->getRequest();
        $this->request->headers->set('Host', '127.0.0.1');
        $this->request->headers->set('User-Agent', 'test');

        config()->set('idempotent.entities.news_post.headers', ['host', 'user-agent']);
        $this->config = (new Config())->resolveConfig($this->request);

        $signature = (new Signature())->makeSignature($this->request, $this->config);
        $this->assertIsString($signature->getSignature());

        $signature = explode(Signature::SIGNATURE_SEPARATOR, $signature->getSignature());

        $this->assertIsArray($signature);
        $this->assertContains('news', $signature);
        $this->assertContains('post', $signature);
        $this->assertContains('title', $signature);
        $this->assertContains('summary', $signature);
        $this->assertContains('127.0.0.1', $signature);
        $this->assertContains('test', $signature);
    }

    /**
     * @test
     * @throws Exception
     */
    public function assert_make_signature_with_servers(): void
    {
        $this->getRequest();
        $this->request->server->set('REMOTE_ADDR', '127.0.0.2');

        config()->set('idempotent.entities.news_post.servers', ['REMOTE_ADDR']);
        $this->config = (new Config())->resolveConfig($this->request);

        $signature = (new Signature())->makeSignature($this->request, $this->config);
        $this->assertIsString($signature->getSignature());

        $signature = explode(Signature::SIGNATURE_SEPARATOR, $signature->getSignature());

        $this->assertIsArray($signature);
        $this->assertContains('news', $signature);
        $this->assertContains('post', $signature);
        $this->assertContains('title', $signature);
        $this->assertContains('summary', $signature);
        $this->assertContains('127.0.0.2', $signature);
    }

    /**
     * @test
     * @throws Exception
     */
    public function assert_make_signature_with_headers_and_servers(): void
    {
        $this->getRequest();
        $this->request->headers->set('Host', '127.0.0.1');
        $this->request->headers->set('User-Agent', 'test');
        $this->request->server->set('REMOTE_ADDR', '127.0.0.2');

        config()->set('idempotent.entities.news_post.headers', ['host', 'user-agent']);
        config()->set('idempotent.entities.news_post.servers', ['REMOTE_ADDR']);
        $this->config = (new Config())->resolveConfig($this->request);

        $signature = (new Signature())->makeSignature($this->request, $this->config);
        $this->assertIsString($signature->getSignature());

        $signature = explode(Signature::SIGNATURE_SEPARATOR, $signature->getSignature());

        $this->assertIsArray($signature);
        $this->assertContains('news', $signature);
        $this->assertContains('post', $signature);
        $this->assertContains('title', $signature);
        $this->assertContains('summary', $signature);
        $this->assertContains('127.0.0.1', $signature);
        $this->assertContains('test', $signature);
        $this->assertContains('127.0.0.2', $signature);
    }

    /**
     * @test
     * @throws Exception
     */
    public function assert_make_signature_with_multiple_values_in_headers_servers(): void
    {
        $this->getRequest();
        $this->request->headers->set('Host', ['127.0.0.1', '10.0.0.1']);
        $this->request->server->set('REMOTE_ADDR', ['127.0.0.2', '10.0.0.2']);

        config()->set('idempotent.entities.news_post.headers', ['host', 'user-agent']);
        config()->set('idempotent.entities.news_post.servers', ['REMOTE_ADDR']);
        $this->config = (new Config())->resolveConfig($this->request);

        $signature = (new Signature())->makeSignature($this->request, $this->config);
        $this->assertIsString($signature->getSignature());

        $signature = explode(Signature::SIGNATURE_SEPARATOR, $signature->getSignature());

        $this->assertIsArray($signature);
        $this->assertContains('news', $signature);
        $this->assertContains('post', $signature);
        $this->assertContains('title', $signature);
        $this->assertContains('summary', $signature);
        $this->assertContains('127.0.0.1', $signature);
        $this->assertContains('10.0.0.1', $signature);
        $this->assertContains('127.0.0.2', $signature);
        $this->assertContains('10.0.0.2', $signature);
    }

    /**
     * @test
     * @throws Exception
     */
    public function assert_make_signature_works_with_mix_lowercase_uppercase_headers_servers_params(): void
    {
        $this->getRequest();
        $this->request->headers->set('HOst', '127.0.0.1');
        $this->request->server->set('ReMOTe_addR', '127.0.0.2');

        config()->set('idempotent.entities.news_post.headers', ['hoST']);
        config()->set('idempotent.entities.news_post.servers', ['rEMoTE_AddR']);
        $this->config = (new Config())->resolveConfig($this->request);

        $signature = (new Signature())->makeSignature($this->request, $this->config);
        $this->assertIsString($signature->getSignature());

        $signature = explode(Signature::SIGNATURE_SEPARATOR, $signature->getSignature());

        $this->assertIsArray($signature);
        $this->assertContains('news', $signature);
        $this->assertContains('post', $signature);
        $this->assertContains('title', $signature);
        $this->assertContains('summary', $signature);
        $this->assertContains('127.0.0.1', $signature);
        $this->assertContains('127.0.0.2', $signature);
    }

    /**
     * @test
     * @throws Exception
     */
    public function assert_make_signature_works_with_non_existing_key_header(): void
    {
        $this->getRequest();
        $this->request->headers->set('host', '127.0.0.1');
        $this->request->server->set('REMOTE_ADDR', '127.0.0.2');

        config()->set('idempotent.entities.news_post.headers', ['non-existing-header-key']);
        config()->set('idempotent.entities.news_post.servers', ['non-existing-server-key']);
        $this->config = (new Config())->resolveConfig($this->request);

        $signature = (new Signature())->makeSignature($this->request, $this->config);
        $this->assertIsString($signature->getSignature());

        $signature = explode(Signature::SIGNATURE_SEPARATOR, $signature->getSignature());

        $this->assertIsArray($signature);
        $this->assertContains('news', $signature);
        $this->assertContains('post', $signature);
        $this->assertContains('title', $signature);
        $this->assertContains('summary', $signature);
    }
}
