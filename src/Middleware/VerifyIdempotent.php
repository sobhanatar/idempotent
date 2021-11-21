<?php

namespace Sobhanatar\Idempotent\Middleware;

use Closure;
use Exception;
use Sobhanatar\Idempotent\Config;
use Sobhanatar\Idempotent\Idempotent;
use Illuminate\Http\{Request, Response};

class VerifyIdempotent
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
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
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

            [$exists, $result] = $this->idempotent->verify($service, $this->config->getEntity(), $this->config->getEntityConfig(), $hash);
            if ($exists) {
                $response = $this->idempotent->prepareResponse($this->config->getEntity(), $result['response']);
                return $this->response($request, $response, (int)$result['code']);
            }

            $response = $next($request);
            $this->idempotent->update($service, $response, $this->config->getEntity(), $hash);
            return $this->response($request, $response->getContent(), $response->getStatusCode());

        } catch (Exception $e) {
            return response(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
