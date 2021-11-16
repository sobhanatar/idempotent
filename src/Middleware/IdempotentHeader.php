<?php

namespace Sobhanatar\Idempotent\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Sobhanatar\Idempotent\Idempotent;

class IdempotentHeader
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
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            [$entity, $config] = $this->idempotent->resolveEntity($request);
            $this->idempotent->validateEntity($request, $entity, $config);
            $requestBag = [
                'fields' => $request->all(),
                'headers' => $request->headers->all(),
                'servers' => $request->server->all()
            ];
            $signature = $this->idempotent->getSignature($requestBag, $entity, $config);
            $hash = $this->idempotent->hash($signature);
            $request->headers->set(config('idempotent.header'), $hash);

            return $next($request);

        } catch (Exception $e) {
            return response(['message' => $e->getMessage()]);
        }
    }
}
