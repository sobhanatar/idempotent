<?php

namespace Sobhanatar\Idempotent\Middleware;

use Closure;
use Exception;
use Sobhanatar\Idempotent\Idempotent;
use Illuminate\Http\{Request, Response};

class VerifyIdempotent
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
            $service = $this->idempotent->resolveStorageService($config['storage']);
            $requestBag = [
                'fields' => $request->all(),
                'headers' => $request->headers->all(),
                'servers' => $request->server->all()
            ];
            $signature = $this->idempotent->getSignature($requestBag, $entity, $config);
            $hash = $this->idempotent->hash($signature);

            [$exists, $result] = $this->idempotent->verify($service, $entity, $config, $hash);
            if ($exists) {
                $response = $this->idempotent->prepareResponse($entity, $result['response']);
                return $this->response($request, $response, (int)$result['code']);
            }

            $response = $next($request);
            $this->idempotent->update($service, $response, $entity, $hash);
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
