<?php

namespace Sobhanatar\Idempotent;

use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Request as SfRequest;

class Config
{
    private const SEPARATOR = '_';
    private const ROUTE_SEPARATOR = '.';

    private array $config;
    private string $entity;
    private array $entityConfig;

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @param array $config
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    /**
     * @return string
     */
    public function getEntity(): string
    {
        return $this->entity;
    }

    /**
     * @param string $entity
     */
    public function setEntity(string $entity): void
    {
        $this->entity = $entity;
    }

    /**
     * @return array
     */
    public function getEntityConfig(): array
    {
        return $this->entityConfig;
    }

    /**
     * @return string
     */
    public function getStorage(): string
    {
        return $this->entityConfig['storage'];
    }

    /**
     * @return string
     */
    public function getTimeout(): string
    {
        return $this->entityConfig['timeout'];
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->config['table'];
    }

    /**
     * @return array
     */
    public function getRedis(): array
    {
        return $this->config['redis'];
    }

    /**
     * @return int
     */
    public function getTtl(): int
    {
        return (int)$this->entityConfig['ttl'];
    }

    /**
     * @param array $entityConfig
     */
    public function setEntityConfig(array $entityConfig): void
    {
        $this->entityConfig = $entityConfig;
    }

    /**
     * Get the entity's name from the route's name and then acquire its config
     *
     * @param Request $request
     * @return Config
     * @throws Exception
     */
    public function resolveConfig(Request $request): Config
    {
        $this->setConfig(config('idempotent'));

        $route = $request->route();
        $this->setEntity(str_replace(self::ROUTE_SEPARATOR, self::SEPARATOR, $route->getName()));
        $this->setEntityConfig(config(sprintf('idempotent.entities.%s', $this->getEntity())) ?? []);

        if (!$this->getEntityConfig()) {
            throw new Exception(sprintf('Entity `%s` does not exists or is empty', $this->getEntity()));
        }

        if (strtoupper($request->method()) !== SfRequest::METHOD_POST) {
            throw new Exception(sprintf('Route method is not POST, it is %s', $request->method()));
        }

        if (!isset($this->getEntityConfig()['fields'])) {
            throw new Exception('entity\'s field is empty');
        }

        foreach ($this->getEntityConfig()['fields'] as $field) {
            if (!$request->input($field)) {
                throw new Exception(sprintf('%s is in fields but not on request inputs', $field));
            }
        }

        return $this;
    }
}