<?php

namespace Zaimea\OAuth2Client\Core;

use Illuminate\Contracts\Container\Container;
use Zaimea\OAuth2Client\Providers\ProviderAbstract;

/**
 * Manager: resolves provider driver class instances by name.
 *
 * Example:
 *   $manager = app(\Zaimea\OAuth2Client\Core\Manager::class);
 *   $driver = $manager->driver('github');
 */
class Manager
{
    protected Container $app;

    public function __construct(Container $app) {
        $this->app = $app;
    }

    /**
     * Resolve provider instance by name.
     *
     * @param string $name Lowercase provider key (e.g. 'github', 'google')
     * @return ProviderAbstract
     * @throws \InvalidArgumentException if missing config or class
     */
    public function driver(string $name): ProviderAbstract
    {
        $config = config("oauth2-client.providers.{$name}", []);
        if (empty($config)) {
            throw new \InvalidArgumentException("Unknown oauth provider: {$name}");
        }

        // Convention: Zaimea\OAuth2Client\Providers\{ProviderName}Provider
        $class = '\\Zaimea\\OAuth2Client\\Providers\\' . ucfirst($name) . 'Provider';
        if (!class_exists($class)) {
            throw new \InvalidArgumentException("Provider class {$class} not found");
        }

        return new $class($config);
    }
}
