<?php

namespace Sobhanatar\Idempotent\Facades;

use Illuminate\Support\Facades\Facade;

class Idempotent extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'idempotent';
    }
}
