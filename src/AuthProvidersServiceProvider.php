<?php

namespace Zaimea\OAuth2Client;

use Illuminate\Support\ServiceProvider;

class AuthProvidersServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/oauth2-client.php', 'oauth2-client');

        $this->app->singleton(Manager::class, function ($app) {
            return new Manager($app);
        });

        $this->app->alias(Manager::class, 'auth.providers');
    }

    public function boot()
    {
        // load routes and views
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views/providers', 'oauth2-client');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/oauth2-client.php' => config_path('oauth2-client.php')
            ], 'config');
        }
    }
}
