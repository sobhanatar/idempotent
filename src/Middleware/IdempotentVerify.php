<?php

namespace Sobhanatar\Idempotent\Middleware;

use Closure;
use Exception;
use Sobhanatar\Idempotent\Idempotent;
use Illuminate\Http\{Request, Response};

class IdempotentVerify
{
    /**
     * @var Idempotent $idempotent
     */
    private Idempotent $idempotent;

    /**
     * @param Idempotent $idempotent
     */
    public function __construct(Idempotent $idempotent)
    {
        $this->idempotent = $idempotent;
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
            [$entityName, $entityConfig] = $this->idempotent->resolveEntity($request);
            $storageService = $this->idempotent->resolveStorage($entityConfig['connection']);
            $hash = $this->idempotent->getIdempotentKey($request, $entityName, $entityConfig['fields']);

            [$exists, $result] = $this->idempotent->verify($storageService, $entityName, $entityConfig, $hash);
            if ($exists) {
                $response = $this->idempotent->prepareResponse($entityName, $result['response']);
                return response($response);
            }

            /**@var Response $response */
            $response = $next($request);
            $this->idempotent->update($storageService, $response, $entityName, $hash);
            return response($response->getContent(), $response->getStatusCode());

        } catch (Exception $e) {
            return response(['message' => $e->getMessage()]);
        }
    }
}
