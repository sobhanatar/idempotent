<?php

namespace Sobhanatar\Idempotent\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Sobhanatar\Idempotent\Config;
use Sobhanatar\Idempotent\Idempotent;

class IdempotentHeader
{
    private Idempotent $idempotent;
    private Config $config;

    /**
     * @param Config $config
     * @param Idempotent $idempotent
     */
    public function __construct(Config $config, Idempotent $idempotent)
    {
        $this->config = $config;
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
            $this->config->resolveConfig($request);
            $service = $this->idempotent->resolveStorageService($this->config->getEntityConfig()['storage']);
//            [$entity, $config] = $this->idempotent->resolveEntity($request);
//            $this->idempotent->validateEntity($request, $entity, $config);
//            $service = $this->idempotent->resolveStorageService($config['storage']);
            $requestBag = [
                'fields' => $request->all(),
                'headers' => $request->headers->all(),
                'servers' => $request->server->all()
            ];
            $signature = $this->idempotent->getSignature($requestBag, $this->config->getEntity(), $this->config->getEntityConfig());
            $hash = $this->idempotent->hash($signature);
            $request->headers->set(config('idempotent.header'), $hash);

            return $next($request);

        } catch (Exception $e) {
            return response(['message' => $e->getMessage()]);
        }
    }
}
