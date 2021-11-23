<?php

namespace Sobhanatar\Idempotent;

use Illuminate\Http\Request;

class Signature
{
    public const SIGNATURE_SEPARATOR = '_';

    private string $signature;
    private string $hash;

    /**
     * @param string $signature
     */
    public function setSignature(string $signature): void
    {
        $this->signature = $signature;
    }

    /**
     * @return string
     */
    public function getSignature(): string
    {
        return $this->signature;
    }

    /**
     * @param string $hash
     */
    public function setHash(string $hash): void
    {
        $this->hash = $hash;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * Create Idempotent signature
     *
     * @param Request $request
     * @param Config $config
     * @return Signature
     */
    public function makeSignature(Request $request, Config $config): Signature
    {
        $signature = array_merge(
            [$config->getEntity()],
            $this->prepareFields($request->all() ?? [], $config->getEntityConfig()['fields']),
            $this->prepareHeaders($request->headers->all() ?? [], $config->getEntityConfig()['headers'] ?? []),
            $this->prepareServers($request->server->all() ?? [], $config->getEntityConfig()['servers'] ?? []),
        );

        $this->setSignature(implode(self::SIGNATURE_SEPARATOR, $signature));
        return $this;
    }

    /**
     * Create hash from the signature
     *
     * @return Signature
     */
    public function hash(): Signature
    {
        $this->setHash(hash(config('idempotent.driver', 'sha256'), $this->getSignature()));
        return $this;
    }

    /**
     * Create fields part of signature
     *
     * @param array $requestFields
     * @param array $configFields
     * @return array
     */
    protected function prepareFields(array $requestFields, array $configFields): array
    {
        $fields = [];
        foreach ($configFields as $field) {
            $fields[] = $requestFields[$field];
        }

        return $fields;
    }

    /**
     * Create headers part of signature
     *
     * @param array $requestHeaders
     * @param array $configHeaders
     * @return array
     */
    protected function prepareHeaders(array $requestHeaders, array $configHeaders): array
    {
        if (!count($configHeaders)) {
            return [];
        }

        $requestHeaders = array_change_key_case($requestHeaders, CASE_LOWER);

        $headers = [];
        foreach ($configHeaders as $header) {
            $header = strtolower($header);
            if (!isset($requestHeaders[$header])) {
                continue;
            }

            if (!is_array($requestHeaders[$header])) {
                $headers[] = $requestHeaders[$header];
                continue;
            }

            foreach ($requestHeaders[$header] as $item) {
                $headers[] = $item;
            }
        }

        return $headers;
    }

    /**
     * Create server part of signature
     *
     * @param array $requestServers
     * @param array $configServers
     * @return array
     */
    protected function prepareServers(array $requestServers, array $configServers): array
    {
        if (!count($configServers)) {
            return [];
        }

        $requestServers = array_change_key_case($requestServers, CASE_LOWER);

        $servers = [];
        foreach ($configServers as $server) {
            $server = strtolower($server);
            if (!isset($requestServers[$server])) {
                continue;
            }

            if (!is_array($requestServers[$server])) {
                $servers[] = $requestServers[$server];
                continue;
            }

            foreach ($requestServers[$server] as $item) {
                $servers[] = $item;
            }
        }

        return $servers;
    }
}
