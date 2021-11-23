<?php

namespace Sobhanatar\Idempotent\Tests;

use Sobhanatar\Idempotent\Config;

class ConfigTest extends TestCase
{
    /**
     * @test
     */
    public function assert_non_exist_entity_config_throws_exception(): void
    {
        $this->getRequest(['hello' => 'hello'], 'POST', 'POST', 'news_post', 'news');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(sprintf('Entity `%s` does not exists or is empty', $this->request->route()->getName()));
        (new Config())->resolveConfig($this->request);
    }

    /**
     * @test
     */
    public function assert_non_post_route_throws_exception(): void
    {
        $this->getRequest(['hello' => 'hello'], 'GET', 'POST');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(sprintf('Route method is not POST, it is %s', $this->request->method()));
        (new Config())->resolveConfig($this->request);
    }

    /**
     * @test
     */
    public function assert_empty_fields_route_throws_exception(): void
    {
        config()->set('idempotent.entities.news_post.fields', null);
        $this->getRequest(['non-exist-config' => 'non-exist-value']);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('entity\'s field is empty');
        (new Config())->resolveConfig($this->request);
    }

    /**
     * @test
     */
    public function assert_non_exists_fields_route_throws_exception(): void
    {
        config()->set('idempotent.entities.news_post.fields', ['hello']);
        $this->getRequest(['hello_is_not_there' => 'non-exist-value']);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(sprintf('%s is in fields but not on request inputs', 'hello'));
        (new Config())->resolveConfig($this->request);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function assert_successful_resolve_return_config_entity_entity_config(): void
    {
        config()->set('idempotent.entities.news_post.fields', ['hello']);
        config()->set('idempotent.table', 'idempotent');
        $this->getRequest(['hello' => 'my value']);
        $config = new Config();
        $config->resolveConfig($this->request);

        $this->assertIsArray($config->getConfig());
        $this->assertEquals(config('idempotent.table'), $config->getConfig()['table']);
        $this->assertIsArray($config->getEntityConfig());
        $this->assertIsString($config->getEntity());
        $this->assertEquals($this->request->route()->getName(), $config->getEntity());
        $this->assertEquals('hello', $config->getEntityConfig()['fields'][0]);
    }
}
