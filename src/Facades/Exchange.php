<?php

namespace AliKhedmati\Exchange\Facades;

use Illuminate\Support\Facades\Facade;

class Exchange extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return 'exchange';
    }
}