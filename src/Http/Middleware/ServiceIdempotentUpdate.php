<?php

namespace Sobhanatar\Idempotent\Http\Middleware;

use Closure;

class ServiceIdempotentUpdate
{
    /**
     * Handle the incoming requests.
     *
     * @param \Illuminate\Http\Request $request
     * @param callable $next
     * @return \Illuminate\Http\Response
     */
    public function handle($request, Closure $next)
    {
        $response =  $next($request);

        //todo do my thing

        return $response;
    }

}
