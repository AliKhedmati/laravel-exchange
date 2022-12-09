<?php

namespace AliKhedmati\Exchange;

use AliKhedmati\Exchange\Drivers\Bitpin;
use AliKhedmati\Exchange\Drivers\Nobitex;
use AliKhedmati\Exchange\Drivers\Wallex;
use Illuminate\Support\Manager;

class ExchangeManager extends Manager
{
    /**
     * @return Utils\Utils
     */

    public function utils(): Utils\Utils
    {
        return new Utils\Utils();
    }

    /**
     * @param $exchange
     * @return mixed
     */

    public function exchange($exchange = null): mixed
    {
        return $this->driver($exchange);
    }

    /**
     * @return Nobitex
     */

    public function createNobitexDriver(): Nobitex
    {
        return new Nobitex(
            restApiBase: $this->config->get('exchange.drivers.nobitex.base-url'),
            apiKey: $this->config->get('exchange.drivers.nobitex.api-key'),
        );
    }

    /**
     * @return Wallex
     */

    public function createWallexDriver(): Wallex
    {
        return new Wallex();
    }

    /**
     * @return Bitpin
     */

    public function createBitpinDriver(): Bitpin
    {
        return new Bitpin();
    }

    /**
     * @return string
     */
    
    public function getDefaultDriver(): string
    {
        return $this->config->get('exchange.default-driver');
    }
}