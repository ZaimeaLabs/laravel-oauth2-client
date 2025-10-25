<?php

namespace Zaimea\OAuth2Client\Facades;

use Illuminate\Support\Facades\Facade;

class AuthProviders extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Zaimea\OAuth2Client\Manager::class;
    }
}
