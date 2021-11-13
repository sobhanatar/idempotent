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
            [$entity, $config] = $this->idempotent->resolveEntity($request);
            $this->idempotent->validateEntity($request, $entity, $config);
            $service = $this->idempotent->resolveStorageService($config['connection']);
            $key = $this->idempotent->getIdempotentKey($request, $entity, $config);
            $hash = $this->idempotent->getIdempotentHash($key);

            [$exists, $result] = $this->idempotent->verify($service, $entity, $config, $hash);
            if ($exists) {
                $response = $this->idempotent->prepareResponse($entity, $result['response']);
                return response($response);
            }

            //Do after getting response
            /**@var Response $response */
            $response = $next($request);
            $this->idempotent->update($service, $response, $entity, $hash);
            return response($response->getContent(), $response->getStatusCode());

        } catch (Exception $e) {
            return response(['message' => $e->getMessage()]);
        }
    }
}
