<?php

namespace Zaimea\OAuth2Client\Facades;

use Illuminate\Support\Facades\Facade;

class OAuth2Client extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Zaimea\OAuth2Client\Core\Manager::class;
    }
}
