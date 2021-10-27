<?php

namespace Sobhanatar\Idempotent\Middleware;

use Closure;
use Sobhanatar\Idempotent\Idempotent;
use Illuminate\Http\{Request, Response};
use Sobhanatar\Idempotent\Exceptions\{
    InvalidFieldInputException,
    InvalidConnectionException,
    InvalidMethodException,
    InvalidRouteTypeException,
    InvalidEntityConfigException
};

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
            $hash = $this->idempotent->createHash($request, $entityName, $entityConfig['fields']);
            $storageImp = $this->idempotent->getStorage($entityConfig['connection']);
            $this->idempotent->set($storageImp, $entityName, $entityConfig, $hash);
            $request->headers->set(config('idempotent.header'), $hash);

            $response = $next($request);
//            dump($request->route()->getName());
//            dump(json_encode($request->header(config('idempotent.header'))));
//            dump($response->getStatusCode());
//            dump($response->getContent());
            return response(['message' => 'WIP']);

        } catch (InvalidRouteTypeException | InvalidEntityConfigException | InvalidMethodException |
        InvalidFieldInputException | InvalidConnectionException $e) {
            return response(['message' => $e->getMessage()]);
        }
    }
}
