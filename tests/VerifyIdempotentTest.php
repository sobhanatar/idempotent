<?php

namespace Sobhanatar\Idempotent\Tests;

use JsonException;
use Sobhanatar\Idempotent\Config;
use Sobhanatar\Idempotent\Signature;
use Illuminate\Http\{Request, Response};
use Sobhanatar\Idempotent\StorageService as Storage;
use Sobhanatar\Idempotent\Middleware\VerifyIdempotent;

class VerifyIdempotentTest extends TestCase
{
    /**
     * @var Request
     */
    protected Request $request;

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

        $response = (new VerifyIdempotent(new Config, new Signature, new Storage))->handle($this->request, function (Request $request) {
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
        $this->getRedisConnection();
        $this->firstTimeVerify(Storage::REDIS, 15);
        $key = hash(config('idempotent.driver'), 'news_post_some-title_some-summary');
        $this->redis->del('news_post_' . $key);
    }

    /**
     * @test
     * @throws JsonException
     */
    public function assert_handle_works_with_redis_when_long_process_makes_key_disappear(): void
    {
        $ttl = 2;
        $this->getRequest();
        $this->getRedisConnection();

        config()->set('idempotent.entities.news_post.storage', 'redis');
        config()->set('idempotent.entities.news_post.ttl', $ttl);

        $response = (new VerifyIdempotent(new Config, new Signature, new Storage))->handle($this->request, function ($request) use ($ttl) {
            sleep($ttl + 1);
            return response()->json(['message' => 'Hi Im created'], Response::HTTP_CREATED);
        });

        $this->assertEquals(json_encode(['message' => 'Hi Im created'], JSON_THROW_ON_ERROR), $response->getContent());
        $key = hash(config('idempotent.driver'), 'news_post_title_summary');
        $this->redis->del('news_post_' . $key);
    }

    /**
     * @test
     * @throws JsonException
     */
    public function assert_handle_works_for_two_consecutive_request_redis(): void
    {
        $this->getRequest();
        $this->getRedisConnection();
        $this->firstTimeVerify(Storage::REDIS, 3);

        $response = (new VerifyIdempotent(new Config, new Signature, new Storage))->handle($this->request, function (Request $request) {
            return [true, [
                'status' => Storage::DONE,
                'result' => serialize(json_encode(['message' => 'Hi Im created'])),
                'code' => Response::HTTP_CREATED
            ]];
        });

        $this->assertEquals(json_encode(['message' => 'Hi Im created'], JSON_THROW_ON_ERROR), $response->getContent());
        $key = hash(config('idempotent.driver'), 'news_post_some-title_some-summary');
        $this->redis->del('news_post_' . $key);
    }

    /**
     * @test
     * @throws JsonException
     */
    public function assert_handle_catch_response_from_wrong_entity_name(): void
    {
        $this->getRequest(
            ['title' => 'some-title', 'summary' => 'some-summary'],
            'POST',
            'POST',
            'news',
            'non-existing-route'
        );

        $response = (new VerifyIdempotent(new Config, new Signature, new Storage))->handle($this->request, function (Request $request) {
        });
        $this->assertEquals(
            json_encode(['message' => sprintf('Entity `%s` does not exists or is empty', 'non-existing-route')], JSON_THROW_ON_ERROR),
            $response->getContent()
        );
    }

    /**
     * @test
     * @throws JsonException
     */
    public function assert_handle_catch_response_from_wrong_wrong_method(): void
    {
        $this->getRequest(['title' => 'title', 'summary' => 'summary'], 'GET');
        $response = (new VerifyIdempotent(new Config, new Signature, new Storage))->handle($this->request, function (Request $request) {
        });
        $this->assertEquals(
            json_encode(['message' => sprintf('Route method is not POST, it is %s', $this->request->method())],
                JSON_THROW_ON_ERROR),
            $response->getContent()
        );
    }

    /**
     * @test
     * @throws JsonException
     */
    public function assert_handle_json_response(): void
    {
        $this->getRequest();
        $this->getRedisConnection();
        config()->set('idempotent.entities.news_post.storage', 'redis');
        config()->set('idempotent.entities.news_post.ttl', 2);
        $this->request->headers->set('Accept', 'application/json');

        $response = (new VerifyIdempotent(new Config, new Signature, new Storage))->handle($this->request, function (Request $request) {
            return response(['message' => 'Hi Im created'], Response::HTTP_CREATED);
        });

        $this->assertEquals(json_encode(['message' => 'Hi Im created'], JSON_THROW_ON_ERROR), $response->getContent());
        $key = hash(config('idempotent.driver'), 'news_post_some-title_some-summary');
        $this->redis->del('news_post_' . $key);
    }

    /**
     * It pass the first request and assert the result based on connection
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

        $response = (new VerifyIdempotent(new Config, new Signature, new Storage))->handle($this->request, function (Request $request) {
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
