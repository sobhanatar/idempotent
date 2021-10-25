<?php

namespace Sobhanatar\Idempotent\Http\Middleware;

class ServiceIdempotentVerify
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
        return $next($request);
    }

}
