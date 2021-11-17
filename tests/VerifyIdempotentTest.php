<?php

namespace Sobhanatar\Idempotent\Tests;

use Redis;
use JsonException;
use Illuminate\Routing\Route;
use Sobhanatar\Idempotent\Idempotent;
use Illuminate\Http\{Request, Response};
use Sobhanatar\Idempotent\Middleware\VerifyIdempotent;
use Sobhanatar\Idempotent\Contracts\{Storage, RedisStorage};

class VerifyIdempotentTest extends TestCase
{
    /**
     * @var Request
     */
    private Request $request;

    /**
     * @var Redis
     */
    private Redis $redis;

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

        $response = (new VerifyIdempotent(new Idempotent()))->handle($this->request, function ($request) use ($ttl) {
            sleep($ttl + 1);
            return response()->json(['message' => 'Hi Im created'], Response::HTTP_CREATED);
        });

        $this->assertEquals(json_encode(['message' => 'Hi Im created'], JSON_THROW_ON_ERROR), $response->getContent());
        $key = hash(config('idempotent.driver'), 'news_post_some-title_some-summary');
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

        $response = (new VerifyIdempotent(new Idempotent()))->handle($this->request, function (Request $request) {
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
        $entity = 'non-existing-route';
        $this->getRequest();

        $this->request->setRouteResolver(function () use ($entity) {
            return (new Route(Request::METHOD_POST, $entity, []))->name($entity)->bind($this->request);
        });

        $response = (new VerifyIdempotent(new Idempotent()))->handle($this->request, function (Request $request) {});
        $this->assertEquals(
            json_encode(['message' => sprintf('Entity `%s` does not exists or is empty', $entity)], JSON_THROW_ON_ERROR),
            $response->getContent()
        );
    }

    /**
     * @test
     * @throws JsonException
     */
    public function assert_handle_catch_response_from_wrong_wrong_method(): void
    {
        $entity = 'news_post';
        $this->getRequest();
        $this->request->setMethod(Request::METHOD_GET);
        $this->request->setRouteResolver(function () use ($entity) {
            return (new Route(Request::METHOD_GET, $entity, []))->name($entity)->bind($this->request);
        });

        $response = (new VerifyIdempotent(new Idempotent()))->handle($this->request, function (Request $request) {
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

        $response = (new VerifyIdempotent(new Idempotent()))->handle($this->request, function (Request $request) {
            return response(['message' => 'Hi Im created'], Response::HTTP_CREATED);
        });

        $this->assertEquals(json_encode(['message' => 'Hi Im created'], JSON_THROW_ON_ERROR), $response->getContent());
        $key = hash(config('idempotent.driver'), 'news_post_some-title_some-summary');
        $this->redis->del('news_post_' . $key);
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

    /**
     * Create redis instance and return the result
     *
     * @return bool
     */
    private function getRedisConnection(): bool
    {
        $this->redis = new Redis();
        return $this->redis->connect(
            config('idempotent.redis.host'),
            config('idempotent.redis.port'),
            config('idempotent.redis.timeout'),
            config('idempotent.redis.reserved'),
            config('idempotent.redis.retryInterval'),
            config('idempotent.redis.readTimeout'),
        );

        $this->service = new RedisStorage($this->redis);
    }
}
