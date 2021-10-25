<?php

namespace Sobhanatar\Idempotent;

class Idempotent
{
    public function test()
    {
        return 'it worked!!';
    }

    /**
     * Indicates if Idempotent migrations will be run.
     *
     * @var bool
     */
    public static $runsMigrations = true;

    /**
     * Determine if Idempotent migrations should be run.
     *
     * @return bool
     */
    public static function shouldRunMigrations(): bool
    {
        return static::$runsMigrations;
    }

    /**
     * Configure Idempotent to not register its migrations.
     *
     * @return static
     */
    public static function ignoreMigrations(): self
    {
        static::$runsMigrations = false;

        return new static;
    }
}
