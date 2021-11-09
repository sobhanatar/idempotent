<?php

namespace Sobhanatar\Idempotent\Middleware;

use Closure;
use Exception;
use Sobhanatar\Idempotent\Idempotent;
use Illuminate\Http\{Request, Response};

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
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            [$entityName, $entityConfig] = $this->idempotent->resolveEntity($request);
            $hash = $this->idempotent->getIdempotentKey($request, $entityName, $entityConfig['fields']);
            $request->headers->set(config('idempotent.header'), $hash);

            return $next($request);

        } catch (Exception $e) {
            return response(['message' => $e->getMessage()]);
        }
    }
}
