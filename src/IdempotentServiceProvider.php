<?php

namespace Sobhanatar\Idempotent;

use Illuminate\Support\ServiceProvider;

class IdempotentServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'sobhanatar');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'sobhanatar');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/idempotent.php', 'idempotent');

        // Register the service the package provides.
        $this->app->singleton('idempotent', function ($app) {
            return new Idempotent;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['idempotent'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/idempotent.php' => config_path('idempotent.php'),
        ], 'idempotent.config');

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/sobhanatar'),
        ], 'idempotent.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/sobhanatar'),
        ], 'idempotent.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/sobhanatar'),
        ], 'idempotent.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}
