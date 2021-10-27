<?php

namespace Sobhanatar\Idempotent;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Sobhanatar\Idempotent\Contracts\RedisStorage;
use Sobhanatar\Idempotent\Contracts\DatabaseStorage;
use Sobhanatar\Idempotent\Contracts\StorageInterface;
use Symfony\Component\HttpFoundation\Request as SymphonyRequest;
use Sobhanatar\Idempotent\Exceptions\{InvalidConnectionException,
    InvalidFieldInputException,
    InvalidRouteTypeException,
    InvalidMethodException,
    InvalidEntityConfigException
};

class Idempotent
{
    private const SEPARATOR = '-';
    private const DATABASE = 'database';
    private const CACHE = 'cache';

    /**
     * Get entity from request route's name
     *
     * @param Request $request
     * @return array
     * @throws InvalidEntityConfigException
     * @throws InvalidMethodException
     * @throws InvalidRouteTypeException
     */
    public function getEntity(Request $request): array
    {
        $route = $request->route();
        if (!$route instanceof Route) {
            throw new InvalidRouteTypeException('Route is not defined');
        }

        $entity = str_replace('.', '-', $route->getName());
        $config = config(sprintf('idempotent.entities.%s', $entity));
        if (!isset($config)) {
            throw new InvalidEntityConfigException(sprintf('Entity `%s` does not exists', $entity));
        }

        if (strtoupper($request->method()) !== SymphonyRequest::METHOD_POST) {
            throw new InvalidMethodException('Route method is not POST');
        }

        return [$entity, $config];
    }

    /**
     * @param Request $request
     * @param string $entityName
     * @param array $fields
     * @return string
     * @throws InvalidFieldInputException
     */
    public function createHash(Request $request, string $entityName, array $fields): string
    {
        $data[] = $entityName;
        foreach ($fields as $field) {
            if (!($value = $request->input($field))) {
                throw new InvalidFieldInputException(sprintf('%s is in field but not on request inputs', $field));
            }
            $data[] = $value;
        }

        return hash(config('idempotent.driver', 'sha256'), implode(self::SEPARATOR, $data));
    }

    /**
     * Get the required storage based on entity connection
     *
     * @throws InvalidConnectionException
     */
    public function getStorage(string $connection): StorageInterface
    {
        switch ($connection) {
            case self::DATABASE:
                return new DatabaseStorage();
            case self::CACHE:
                return new RedisStorage();
            default:
                throw new InvalidConnectionException(sprintf('connection `%s` is not supported', $connection));
        }
    }

    public function set(StorageInterface $storage, string $entityName, array $entityConfig, string $hash): void
    {
        $storage->set($entityName, $entityConfig, $hash);
    }
}
