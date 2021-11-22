<?php

namespace Sobhanatar\Idempotent\Tests;

use JsonException;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Sobhanatar\Idempotent\{Config, Idempotent, Signature};
use Sobhanatar\Idempotent\Middleware\IdempotentHeader;

class IdempotentHeaderTest extends TestCase
{
    use Signature;

    /**
     * @test
     */
    public function assert_handle_works(): void
    {
        // Given we have a request
        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $request->merge(['title' => 'some-title', 'summary' => 'some-summary']);

        $route = new Route(Request::METHOD_POST, 'news_post', []);
        $route->name('news_post');

        $request->setRouteResolver(function () use ($request, $route) {
            return $route->bind($request);
        });

        (new IdempotentHeader(new Config(), new Idempotent()))->handle($request, function (Request $request) {
            $actualHash = $request->header(config('idempotent.header'));
            $expectedHash = hash(
                config('idempotent.driver'),
                implode($this->signatureSeparator, ['news_post_', 'some-title', 'some-summary'])
            );
            $this->assertEquals($expectedHash, $actualHash);
        });
    }

    /**
     * @test
     * @throws JsonException
     */
    public function assert_handle_throw_error_on_non_exist_entity(): void
    {
        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $request->merge(['title' => 'some-title', 'summary' => 'some-summary']);

        $request->setRouteResolver(function () use ($request) {
            return (new Route(Request::METHOD_POST, 'news_post', []))->name('news_posts')->bind($request);
        });

        $response = (new IdempotentHeader(new Config(), new Idempotent()))->handle($request, function (Request $request) {
        });

        $this->assertEquals(
            $response->getContent(),
            json_encode(
                ['message' => sprintf('Entity `%s` does not exists or is empty', 'news_posts')],
                JSON_THROW_ON_ERROR
            )
        );
    }

    /**
     * @test
     * @throws JsonException
     */
    public function assert_handle_throw_error_on_non_post_requests(): void
    {
        $request = new Request();
        $request->merge(['title' => 'some-title', 'summary' => 'some-summary']);

        $request->setRouteResolver(function () use ($request) {
            return (new Route(Request::METHOD_POST, 'news_post', []))->name('news_post')->bind($request);
        });

        $response = (new IdempotentHeader(new Config(), new Idempotent()))->handle($request, function (Request $request) {
        });

        $this->assertEquals(
            $response->getContent(),
            json_encode(
                ['message' => sprintf('Route method is not POST, it is %s', $request->method())],
                JSON_THROW_ON_ERROR
            )
        );
    }

    /**
     * @test
     * @throws JsonException
     */
    public function assert_handle_throw_error_on_non_existing_config(): void
    {
        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $request->merge(['title' => 'some-title', 'summary' => 'some-summary']);

        $request->setRouteResolver(function () use ($request) {
            return (new Route(Request::METHOD_POST, 'news_post', []))->name('news_post')->bind($request);
        });

        config()->set('idempotent.entities.news_post.fields');
        $response = (new IdempotentHeader(new Config(), new Idempotent()))->handle($request, function (Request $request) {
        });

        $this->assertEquals(
            $response->getContent(),
            json_encode(['message' => 'entity\'s field is empty'], JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @test
     * @throws JsonException
     */
    public function assert_handle_throw_error_on_non_existing_body(): void
    {
        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $request->merge(['not-title' => 'some-title', 'summary' => 'some-summary']);

        $request->setRouteResolver(function () use ($request) {
            return (new Route(Request::METHOD_POST, 'news_post', []))->name('news_post')->bind($request);
        });

        $response = (new IdempotentHeader(new Config(), new Idempotent()))->handle($request, function (Request $request) {
        });

        $this->assertEquals(
            $response->getContent(),
            json_encode(['message' => 'title is in fields but not on request inputs'], JSON_THROW_ON_ERROR)
        );
    }
}
