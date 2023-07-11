<?php

namespace Sobhanatar\Idempotent\Middleware;

use Closure;
use Exception;
use Illuminate\Http\{JsonResponse, Request, Response};
use Sobhanatar\Idempotent\{Config, Signature, StorageService};
use Symfony\Component\CssSelector\Exception\InternalErrorException;

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
     * @return JsonResponse
     */
    public function handle(Request $request, Closure $next): JsonResponse
    {
        try {
            $this->config->resolveConfig($request);
            $this->signature->makeSignature($request, $this->config)->hash();

            $storage = $this->storageService
                ->resolveStrategy($this->config)
                ->verify($this->config->getEntity(), $this->config->getEntityConfig(), $this->signature->getHash());
            if ($this->storageService->exists()) {
                $response = $this->prepareResponse($this->config->getEntity(), $this->storageService->getResponse()['response']);
                $statusCode = $this->storageService->getResponse()['code'] != null ? (int)$this->storageService->getResponse()['code'] : 401;
                return $this->generateResponse(json_decode($response, true), $statusCode);
//                return $this->response($request, $response, (int)$this->storageService->getResponse()['code']);
            }

            $response = $next($request);
            $storage->update($response, $this->config->getEntity(), $this->signature->getHash());
            return $this->generateResponse(json_decode($response->getContent(), true), $response->getStatusCode());
//            return $this->response($request, $response->getContent(), $response->getStatusCode());

        } catch (Exception $e) {
            return fdb_fail_api_response(__('idempotent.fail'), __('idempotent.fail'));
//            return response(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
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


    public function generateResponse(array $response, int $statusCode): JsonResponse
    {
        return fdb_success_api_response($response['message'], $response['data']['result'], $statusCode, $response['data']['total'], $response['data']['per_page']);
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
