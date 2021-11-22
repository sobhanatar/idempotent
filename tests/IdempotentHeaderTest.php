<?php

namespace Sobhanatar\Idempotent\Tests;

use Illuminate\Http\Request;
use Sobhanatar\Idempotent\{Config, Idempotent, Signature};
use Sobhanatar\Idempotent\Middleware\IdempotentHeader;

class IdempotentHeaderTest extends TestCase
{
    /**
     * @test
     */
    public function assert_handle_works(): void
    {
        $this->getRequest(['title' => 'some-title', 'summary' => 'some-summary']);

        (new IdempotentHeader(new Config, new Signature, new Idempotent))->handle($this->request, function (Request $request) {
            $actualHash = $request->header(config('idempotent.header'));
            $expectedHash = hash(
                config('idempotent.driver'),
                implode(Signature::SIGNATURE_SEPARATOR, ['news_post_', 'some-title', 'some-summary'])
            );
            $this->assertEquals($expectedHash, $actualHash);
        });
    }

    /**
     * @test
     * @throws \JsonException
     */
    public function assert_handle_throw_error_on_non_exist_entity(): void
    {
        $this->getRequest(
            ['title' => 'some-title', 'summary' => 'some-summary'],
            Request::METHOD_POST,
            Request::METHOD_POST,
            'news',
            'news'
        );

        $response = (new IdempotentHeader(new Config, new Signature, new Idempotent))->handle($this->request, function (Request $request) {
        });

        $this->assertEquals(
            $response->getContent(),
            json_encode(
                ['message' => sprintf('Entity `%s` does not exists or is empty', 'news')],
                JSON_THROW_ON_ERROR
            )
        );
    }

    /**
     * @test
     * @throws \JsonException
     */
    public function assert_handle_throw_error_on_non_post_requests(): void
    {
        $this->getRequest(['title' => 'some-title', 'summary' => 'some-summary'], Request::METHOD_GET);
        $response = (new IdempotentHeader(new Config, new Signature, new Idempotent))->handle($this->request, function (Request $request) {
        });

        $this->assertEquals(
            $response->getContent(),
            json_encode(
                ['message' => sprintf('Route method is not POST, it is %s', $this->request->method())],
                JSON_THROW_ON_ERROR
            )
        );
    }

    /**
     * @test
     * @throws \JsonException
     */
    public function assert_handle_throw_error_on_non_existing_fields(): void
    {
        $this->getRequest(['title' => 'some-title', 'summary' => 'some-summary']);
        config()->set('idempotent.entities.news_post.fields');
        $response = (new IdempotentHeader(new Config, new Signature, new Idempotent))->handle($this->request, function (Request $request) {
        });

        $this->assertEquals(
            $response->getContent(),
            json_encode(['message' => 'entity\'s field is empty'], JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @test
     * @throws \JsonException
     */
    public function assert_handle_throw_error_on_non_existing_body_field(): void
    {
        $this->getRequest(['not-title' => 'some-title', 'summary' => 'some-summary']);
        $response = (new IdempotentHeader(new Config, new Signature, new Idempotent))->handle($this->request, function (Request $request) {
        });

        $this->assertEquals(
            $response->getContent(),
            json_encode(['message' => 'title is in fields but not on request inputs'], JSON_THROW_ON_ERROR)
        );
    }
}
