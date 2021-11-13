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
    public const SEPARATOR = '_';

    public const ROUTE_SEPARATOR = '.';

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

        return [$entity, $config];
    }

    /**
     * Validate entity's requirement
     *
     * @param Request $request
     * @param string $entity
     * @param array|null $config
     * @return void
     */
    public function validateEntity(Request $request, string $entity, ?array $config): void
    {
        if (!isset($config) || count($config) === 0) {
            throw new InvalidArgumentException(sprintf('Entity `%s` does not exists or is empty', $entity));
        }

        if (strtoupper($request->method()) !== SymphonyRequest::METHOD_POST) {
            throw new MethodNotAllowedException([SymphonyRequest::METHOD_POST], 'Route method is not POST');
        }

        if (!isset($config['fields'])) {
            throw new InvalidArgumentException('entity\'s field is empty');
        }

        foreach ($config['fields'] as $field) {
            if (!$request->input($field)) {
                throw new InvalidArgumentException(sprintf('%s is in fields but not on request inputs', $field));
            }
        }
    }

    /**
     * Get the required storage based on entity connection
     *
     * @throws InvalidArgumentException
     */
    public function resolveStorageService(string $connection): Storage
    {
        switch ($connection) {
            case Storage::MYSQL:
                return new MysqlStorage(DB::connection(Storage::MYSQL)->getPdo());
            case Storage::REDIS:
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
     * Create Idempotent signature based on fields and headers
     *
     * @param array $requestBag
     * @param string $entity
     * @param array $config
     * @return string
     */
    public function makeSignature(array $requestBag, string $entity, array $config): string
    {
        $baseSignature = array_merge(
            [$entity],
            $this->getFieldsFromRequest($requestBag['fields'], $config['fields']),
            $this->getHeadersFromRequest($requestBag['headers'], $config['headers']),
            $this->getServerFromRequest($requestBag['servers'], $config['servers']),
        );

        return implode(self::SEPARATOR, $baseSignature);
    }

    /**
     * Create hash from the request signature
     *
     * @param string $key
     * @return string
     */
    public function hash(string $key): string
    {
        return hash(config('idempotent.driver', 'sha256'), $key);
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

    /**
     * Create fields part of signature
     *
     * @param array $requestFields
     * @param array $configFields
     * @return array
     */
    protected function getFieldsFromRequest(array $requestFields, array $configFields): array
    {
        foreach ($configFields as $field) {
            $data[] = $requestFields[$field];
        }

        return $data ?? [];
    }

    /**
     * Create headers part of signature
     *
     * @param array $requestHeaders
     * @param array $configHeaders
     * @return array
     */
    protected function getHeadersFromRequest(array $requestHeaders, array $configHeaders): array
    {
        if (!isset($configHeaders)) {
            return [];
        }

        $data = [];
        foreach ($configHeaders ?? [] as $header) {
            // headers return in lower case from symphony
            $header = strtolower($header);
            if (!isset($requestHeaders[$header])) {
                continue;
            }

            if (!is_array($requestHeaders[$header])) {
                $data[] = $requestHeaders[$header];
                continue;
            }

            foreach ($requestHeaders[$header] as $item) {
                $data[] = $item;
            }
        }

        return $data;
    }

    /**
     * Create server part of signature
     *
     * @param array $requestServers
     * @param array $configServers
     * @return array
     */
    protected function getServerFromRequest(array $requestServers, array $configServers): array
    {
        if (!isset($configServers)) {
            return [];
        }

        $data = [];
        foreach ($configServers ?? [] as $server) {
            if (!isset($requestServers[$server])) {
                continue;
            }

            if (!is_array($requestServers[$server])) {
                $data[] = $requestServers[$server];
                continue;
            }

            foreach ($requestServers[$server] as $item) {
                $data[] = $item;
            }
        }

        return $data;
    }
}
