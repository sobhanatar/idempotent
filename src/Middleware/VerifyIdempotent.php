<?php

namespace Sobhanatar\Idempotent\Middleware;

use Closure;
use Exception;
use Illuminate\Http\{Request, Response};
use Sobhanatar\Idempotent\{Config, Signature, StorageService};

class VerifyIdempotent
{
    private Config $config;
    private Signature $signature;
    private StorageService $storageService;

    /**
     * @param Config $config
     * @param Signature $signature
     * @param StorageService $storageService
     */
    public function __construct(Config $config, Signature $signature, StorageService $storageService)
    {
        $this->config = $config;
        $this->signature = $signature;
        $this->storageService = $storageService;
    }

    /**
     * Handle the incoming requests.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $this->config->resolveConfig($request);
            $this->signature->makeSignature($request, $this->config)->hash();

            $storage = $this->storageService
                ->resolveStrategy($this->config)
                ->verify($this->config->getEntity(), $this->config->getEntityConfig(), $this->signature->getHash());
            if ($this->storageService->exists()) {
                $response = $this->prepareResponse($this->config->getEntity(), $this->storageService->getResponse()['response']);
                return $this->response($request, $response, (int)$this->storageService->getResponse()['code']);
            }

            $response = $next($request);
            $storage->update($response, $this->config->getEntity(), $this->signature->getHash());
            return $this->response($request, $response->getContent(), $response->getStatusCode());

        } catch (Exception $e) {
            return response(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Prepare response
     *
     * @param string $entity
     * @param string|null $response
     * @return string
     */
    public function prepareResponse(string $entity, ?string $response): string
    {
        return unserialize($response) ?? trans('idempotent.' . $entity);
    }

    /**
     * Prepare response
     *
     * @param Request $request
     * @param string $response
     * @param int $code
     * @return Response
     */
    protected function response(Request $request, string $response, int $code): Response
    {
        return $request->expectsJson()
            ? response($response, $code)->header('Content-Type', 'application/json')
            : response($response, $code);
    }
}
