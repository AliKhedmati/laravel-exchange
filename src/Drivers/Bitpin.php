<?php

namespace Alikhedmati\Exchange\Drivers;

use Illuminate\Support\Collection;

class Bitpin
{

    public function getMarkets(): Collection
    {
        // TODO: Implement getMarkets() method.
    }

    public function getProfile(): Collection
    {
        // TODO: Implement getProfile() method.
    }

    public function getOrders(): Collection
    {
        // TODO: Implement getOrders() method.
    }

    public function getOrder(string $order): Collection
    {
        // TODO: Implement getOrder() method.
    }

    public function getWallets(): Collection
    {
        // TODO: Implement getWallets() method.
    }

    public function getMarketTrades(string $market): Collection
    {
        // TODO: Implement getMarketTrades() method.
    }

    public function getMarketOrderBook(string $market): Collection
    {
        // TODO: Implement getMarketOrderBook() method.
    }

    public function getMarketTicker(string $market): Collection
    {
        // TODO: Implement getMarketTicker() method.
    }

    public function getMarketCandles(string $market, string $starting_from, string $ending_at): Collection
    {
        // TODO: Implement getMarketCandles() method.
    }

    public function createOrder(string $market, float $quantity, string $side, string $type, ?float $originalPrice = null): Collection
    {
        // TODO: Implement createOrder() method.
    }

    public function cancelOrder(string $order): Collection
    {
        // TODO: Implement cancelOrder() method.
    }
}
