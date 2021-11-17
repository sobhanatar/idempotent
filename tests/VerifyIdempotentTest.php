<?php

namespace Sobhanatar\Idempotent\Tests;

use Illuminate\Routing\Route;
use JsonException;
use Sobhanatar\Idempotent\Idempotent;
use Illuminate\Http\{Request, Response};
use Sobhanatar\Idempotent\Contracts\Storage;
use Sobhanatar\Idempotent\Middleware\VerifyIdempotent;

class VerifyIdempotentTest extends TestCase
{
    /**
     * @var Request
     */
    private Request $request;

    /**
     * @test
     * @throws JsonException
     */
    public function assert_handle_works_with_mysql(): void
    {
        $this->getRequest();
        $this->firstTimeVerify(Storage::MYSQL, 10);
    }

    /**
     * @test
     * @throws JsonException
     */
    public function assert_handle_works_for_two_consecutive_request_with_mysql(): void
    {
        $this->getRequest();
        $this->firstTimeVerify(Storage::MYSQL, 10);

        $response = (new VerifyIdempotent(new Idempotent()))->handle($this->request, function (Request $request) {
            return [true, [
                'result' => serialize(json_encode(['message' => 'Hi Im created'])),
                'code' => Response::HTTP_CREATED
            ]];
        });

        $this->assertEquals(json_encode(['message' => 'Hi Im created'], JSON_THROW_ON_ERROR), $response->getContent());
        $this->assertDatabaseHas(
            config('idempotent.table'),
            [
                'status' => Storage::DONE,
                'code' => Response::HTTP_CREATED,
                'response' => serialize(json_encode(['message' => 'Hi Im created']))
            ],
            'mysql'
        );
        $this->assertDatabaseCount(config('idempotent.table'), 1, 'mysql');
    }

    /**
     * @test
     * @throws JsonException
     */
    public function assert_handle_works_with_redis(): void
    {
        $this->getRequest();
        $this->firstTimeVerify(Storage::REDIS, 15);
    }

    /**
     * @test
     * @throws JsonException
     */
    public function assert_handle_works_for_two_consecutive_request_redis(): void
    {
        $this->getRequest();
        $this->firstTimeVerify(Storage::REDIS, 3);

        $response = (new VerifyIdempotent(new Idempotent()))->handle($this->request, function (Request $request) {
            return [true, [
                'status' => Storage::DONE,
                'result' => serialize(json_encode(['message' => 'Hi Im created'])),
                'code' => Response::HTTP_CREATED
            ]];
        });

        $this->assertEquals(json_encode(['message' => 'Hi Im created'], JSON_THROW_ON_ERROR), $response->getContent());
    }

    /**
     * Create an instance
     */
    private function getRequest(): void
    {
        $this->request = new Request([], ['title' => 'some-title', 'summary' => 'some-summary']);
        $this->request->setMethod(Request::METHOD_POST);

        $this->request->setRouteResolver(function () {
            return (new Route(Request::METHOD_POST, 'news_post', []))->name('news_post')->bind($this->request);
        });
    }

    /**
     * It pass the first handle and assert the result based on connection
     *
     * @param string $connection
     * @param int $ttl
     * @throws JsonException
     */
    private function firstTimeVerify(string $connection, int $ttl): void
    {
        config()->set('idempotent.entities.news_post.storage', $connection);
        config()->set('idempotent.entities.news_post.ttl', $ttl);
        if ($connection === Storage::MYSQL) {
            config()->set('idempotent.entities.news_post.timeout', 10);
            $this->loadMigrationsFrom(self::MIGRATION_PATH);
        }

        $response = (new VerifyIdempotent(new Idempotent()))->handle($this->request, function (Request $request) {
            return response()->json(['message' => 'Hi Im created'], Response::HTTP_CREATED);
        });

        $this->assertEquals(json_encode(['message' => 'Hi Im created'], JSON_THROW_ON_ERROR), $response->getContent());
        if ($connection === Storage::MYSQL) {
            $this->assertDatabaseHas(
                config('idempotent.table'),
                [
                    'status' => Storage::DONE,
                    'code' => Response::HTTP_CREATED,
                    'response' => serialize(json_encode(['message' => 'Hi Im created']))
                ],
                'mysql'
            );
            $this->assertDatabaseCount(config('idempotent.table'), 1, 'mysql');
        }
    }
}
