<?php

namespace Alikhedmati\Exchange\Drivers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;

class Wallex
{
    /**
     * Base endpoint.
     */

    const mainnetRestApiBase = 'https://api.wallex.ir/v1/';

    /**
     * @var string
     */

    private string $restApiBase;

    /**
     * Wallex Class Constructor.
     */

    public function __construct()
    {
        $this->restApiBase = self::mainnetRestApiBase;
    }

    /**
     * @param bool $isAuthenticated
     * @return Client
     */

    private function client(bool $isAuthenticated = false): Client
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent'    =>  'TraderBot/' . config('app.name')
        ];

        if ($isAuthenticated){

            $headers['x-api-key'] = config('settings.wallex-api-key');

        }

        return new Client([
            'base_uri' => $this->restApiBase,
            'headers' => $headers,
            'http_errors' => false
        ]);
    }

    /**
     * @return Collection
     * @throws GuzzleException
     */

    public function getMarkets(): Collection
    {
        return collect(json_decode($this->client()->get('markets')->getBody()->getContents())->result->symbols)
            ->transform(fn($value, $key) => $value->stats->bidPrice)
            ->mapWithKeys(fn($value, $key) => [str_contains($key, 'TMN') ? str_replace('TMN', 'IRT', $key) : $key => $value]);
    }

    /**
     * @return Collection
     * @throws GuzzleException
     */

    public function getProfile(): Collection
    {
        return collect(json_decode($this->client(true)->get('account/profile')->getBody()->getContents()));
    }

    /**
     * @return Collection
     * @throws GuzzleException
     */

    public function getAllOrders(): Collection
    {
        return collect(json_decode($this->client(true)->get('account/trades')->getBody()->getContents()));
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

    public function getOrders(): Collection
    {
        // TODO: Implement getOrders() method.
    }

    public function getOrder(string $order): Collection
    {
        // TODO: Implement getOrder() method.
    }

    public function createOrder(string $market, float $quantity, string $side, string $type, ?float $originalPrice = null): Collection
    {
        // TODO: Implement createOrder() method.
    }

    public function cancelOrder(string $order): Collection
    {
        // TODO: Implement cancelOrder() method.
    }

    public function getWallets(): Collection
    {
        // TODO: Implement getWallets() method.
    }
}
