<?php

namespace Sobhanatar\Idempotent\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Sobhanatar\Idempotent\Config;
use Sobhanatar\Idempotent\Idempotent;
use Sobhanatar\Idempotent\Signature;

class IdempotentHeader
{
    private Idempotent $idempotent;
    private Config $config;
    private Signature $signature;

    /**
     * @param Config $config
     * @param Signature $signature
     * @param Idempotent $idempotent
     */
    public function __construct(Config $config, Signature $signature, Idempotent $idempotent)
    {
        $this->config = $config;
        $this->signature = $signature;
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
            $this->signature->makeSignature($request, $this->config)->hash();
            $request->headers->set(config('idempotent.header'), $this->signature->getHash());

            return $next($request);

        } catch (Exception $e) {
            return response(['message' => $e->getMessage()]);
        }
    }
}
