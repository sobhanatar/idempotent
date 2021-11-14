<?php

namespace Sobhanatar\Idempotent;

trait Signature
{
    public string $signatureSeparator = '_';

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
        $signature = array_merge(
            [$entity],
            $this->getFieldsFromRequest($requestBag['fields'], $config['fields']),
            $this->getHeadersFromRequest($requestBag['headers'], $config['headers'] ?? []),
            $this->getServerFromRequest($requestBag['servers'], $config['servers'] ?? []),
        );

        return implode($this->signatureSeparator, $signature);
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
    protected function getServerFromRequest(array $requestServers, array $configServers): array
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
