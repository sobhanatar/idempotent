<?php

namespace Sobhanatar\Idempotent;

use Redis;
use Exception;
use JsonException;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Request as SymphonyRequest;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Sobhanatar\Idempotent\Contracts\{Storage, RedisStorage, MysqlStorage};

class Idempotent
{
    private const SEPARATOR = '-';
    private const ROUTE_SEPARATOR = '.';

    /**
     * Get the entity's name from the route's name and then acquire its config
     *
     * @param Request $request
     * @return array
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function resolveEntity(Request $request): array
    {
        $route = $request->route();
        if (!$route instanceof Route) {
            throw new Exception('Route is not defined');
        }

        $entity = str_replace(self::ROUTE_SEPARATOR, self::SEPARATOR, $route->getName());
        $config = config(sprintf('idempotent.entities.%s', $entity));
        if (!isset($config)) {
            throw new InvalidArgumentException(sprintf('Entity `%s` does not exists', $entity));
        }

        if (strtoupper($request->method()) !== SymphonyRequest::METHOD_POST) {
            throw new MethodNotAllowedException([SymphonyRequest::METHOD_POST], 'Route method is not POST');
        }

        foreach ($config['fields'] as $field) {
            if (!$request->input($field)) {
                throw new InvalidArgumentException(sprintf('%s is in fields but not on request inputs', $field));
            }
        }

        return [$entity, $config];
    }

    /**
     * Get the required storage based on entity connection
     *
     * @throws InvalidArgumentException
     */
    public function resolveStorage(string $connection): Storage
    {
        switch ($connection) {
            case 'mysql':
                return new MysqlStorage(DB::connection('mysql')->getPdo());
            case 'redis':
                $redis = new Redis();
                $redis->connect(
                    config('idempotent.redis.host'),
                    config('idempotent.redis.port'),
                    config('idempotent.redis.timeout'),
                    config('idempotent.redis.reserved'),
                    config('idempotent.redis.retryInterval'),
                    config('idempotent.redis.readTimeout'),
                );
                return new RedisStorage($redis);
            default:
                throw new InvalidArgumentException(sprintf('connection `%s` is not supported', $connection));
        }
    }

    /**
     * @param Request $request
     * @param string $entityName
     * @param array $fields
     * @return string
     * @throws InvalidArgumentException
     */
    public function getIdempotentKey(Request $request, string $entityName, array $fields): string
    {
        $data[] = $entityName;
        foreach ($fields as $field) {
            $data[] = $request->input($field);
        }

        return hash(config('idempotent.driver', 'sha256'), implode(self::SEPARATOR, $data));
    }

    /**
     * Set data into shared memory
     *
     * @param Storage $storage
     * @param string $entityName
     * @param array $entityConfig
     * @param string $hash
     * @return array
     * @throws Exception
     */
    public function verify(Storage $storage, string $entityName, array $entityConfig, string $hash): array
    {
        return $storage->verify($entityName, $entityConfig, $hash);
    }

    /**
     * update data of shared storage
     *
     * @param Storage $storage
     * @param $response
     * @param string $entityName
     * @param string $hash
     * @return void
     * @throws Exception
     */
    public function update(Storage $storage, $response, string $entityName, string $hash): void
    {
        $storage->update($response, $entityName, $hash);
    }

    /**
     * Prepare response
     *
     * @param string $entity
     * @param string|null $response
     * @return string
     * @throws JsonException
     */
    public function prepareResponse(string $entity, ?string $response): string
    {
        $res = $response ?? trans('idempotent.' . $entity);

        return json_encode($res, JSON_THROW_ON_ERROR);
    }
}
