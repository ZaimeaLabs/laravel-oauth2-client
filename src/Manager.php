<?php

namespace Zaimea\OAuth2Client;

use Illuminate\Contracts\Container\Container;
use Zaimea\OAuth2Client\Providers\ProviderAbstract;

class Manager
{
    protected Container $app;

    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    public function driver(string $name): ProviderAbstract
    {
        $config = config('oauth2-client.providers.'.$name, []);
        if (empty($config)) {
            throw new \InvalidArgumentException("Unknown oauth provider: {$name}");
        }
        $class = '\\Zaimea\\OAuth2Client\\Providers\\'.ucfirst($name).'Provider';
        if (!class_exists($class)) {
            throw new \InvalidArgumentException("Provider class {$class} not found");
        }
        return new $class($config);
    }
}
