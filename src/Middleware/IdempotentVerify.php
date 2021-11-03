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
            [$entityName, $entityConfig] = $this->idempotent->getEntity($request);
            $storageService = $this->idempotent->getStorageService($entityConfig['connection']);
            $hash = $this->idempotent->createHash($request, $entityName, $entityConfig['fields']);
            [$exists, $result] = $this->idempotent->set($storageService, $entityName, $entityConfig, $hash);
            if ($exists) {
                $response = $this->idempotent->prepareResponse($entityName, $result['response']);
                return response($response);
            }

            $request->headers->set(config('idempotent.header'), $hash);
            $response = $next($request);
//            dump($request->route()->getName());
//            dump(json_encode($request->header(config('idempotent.header'))));
//            dump($response->getStatusCode());
//            dump($response->getContent());
            return response(['message' => 'WIP']);

        } catch (Exception $e) {
            return response(['message' => $e->getMessage()]);
        }
    }
}
