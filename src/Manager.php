<?php

namespace Zaimea\OAuth2Client;

use Illuminate\Contracts\Container\Container;
use Zaimea\OAuth2Client\Providers\ProviderAbstract;

class Manager
{
    protected Container $app;
    protected array $drivers = [];

    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    public function driver(string $name): ProviderAbstract
    {
        if (isset($this->drivers[$name])) {
            return $this->drivers[$name];
        }

        $config = config('oauth2-client.providers.'.$name);
        if (!$config) {
            throw new \InvalidArgumentException("Provider [{$name}] is not defined");
        }

        $class = $this->providerClass($config['driver'] ?? $name);
        $instance = new $class($config);
        $this->drivers[$name] = $instance;

        return $instance;
    }

    protected function providerClass(string $driver): string
    {
        $map = [
            'github' => Providers\GithubProvider::class,
            'google' => Providers\GoogleProvider::class,
        ];
        return $map[$driver] ?? Providers\ProviderAbstract::class;
    }
}
