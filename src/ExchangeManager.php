<?php

namespace AliKhedmati\Exchange;

use Alikhedmati\Exchange\Exchanges\Bitpin;
use Alikhedmati\Exchange\Exchanges\Nobitex;
use Alikhedmati\Exchange\Exchanges\Wallex;
use Illuminate\Support\Manager;

class ExchangeManager extends Manager
{
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