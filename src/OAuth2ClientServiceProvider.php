<?php

namespace Zaimea\OAuth2Client;

use Illuminate\Support\ServiceProvider;

class OAuth2ClientServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/oauth2-client.php', 'oauth2-client');

        $this->app->singleton(\Zaimea\OAuth2Client\Core\Manager::class, function($app) {
            return new \Zaimea\OAuth2Client\Core\Manager($app);
        });
        // optional alias binding
        $this->app->alias(\Zaimea\OAuth2Client\Core\Manager::class, 'zaimea.oauth2client');
    }

    public function boot()
    {
        $this->publishes([__DIR__.'/../config/oauth2-client.php' => config_path('oauth2-client.php')], 'config');
        $this->loadRoutesFrom(__DIR__.'/../routes/oauth2-client.php');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views/oauth2-client', 'oauth2-client');
        $this->publishes([__DIR__.'/../resources/views/oauth2-client' => resource_path('views/vendor/oauth2-client')], 'views');
    }
}
