<?php

namespace Sobhanatar\Idempotent;

use Illuminate\Support\ServiceProvider;
use Sobhanatar\Idempotent\Console\PurgeCommand;

class IdempotentServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        // Register the service the package provides.
        $this->app->singleton('idempotent', function ($app) {
            return new Idempotent;
        });

        if (!app()->configurationIsCached()) {
            $this->mergeConfigFrom(__DIR__ . '/../config/idempotent.php', 'idempotent');
        }
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->registerMigrations();

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'idempotent-migrations');

            $this->publishes([
                __DIR__ . '/../config/idempotent.php' => config_path('idempotent.php'),
            ], 'idempotent-config');

            $this->commands([
                // PurgeCommand::class,
            ]);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return ['idempotent'];
    }

    /**
     * Register Sanctum's migration files.
     *
     * @return void
     */
    protected function registerMigrations(): void
    {
        if (Idempotent::shouldRunMigrations()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }
}
