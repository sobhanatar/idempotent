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
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'idempotent-migration');

        $this->publishes([
            __DIR__ . '/../config/idempotent.php' => config_path('idempotent.php'),
        ], 'idempotent-config');

        $this->publishes([
            __DIR__ . '/../resources/lang/idempotent.php' => lang_path('en/idempotent.php'),
        ], 'idempotent-language');

        if ($this->app->runningInConsole()) {
            $this->commands([
                PurgeCommand::class,
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
}
