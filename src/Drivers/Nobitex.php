<?php

namespace Alikhedmati\Exchange\Drivers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Nobitex
{
    /**
     * Nobitex Class Constructor.
     */

    public function __construct(private readonly string $restApiBase, private readonly string $apiKey) {}

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

            $headers['Authorization'] = 'Token ' . $this->apiKey;

        }

        return new Client([
            'base_uri' => $this->restApiBase,
            'headers' =>    $headers,
            'http_errors' => false
        ]);
    }

    /**
     * @return string
     */

    public function getAccessToken(): string
    {
        return config('crypto-exchange.providers.nobitex.api-key');
    }

    /**
     * @return Collection
     * @throws GuzzleException|CryptoExchangeException
     */

    public function getMarkets(): Collection
    {
        /**
         * Make request.
         */

        $request = $this->client()->get('v2/orderbook/all');

        /**
         * Handle failure.
         */

        if ($request->getStatusCode() != 200){

            throw new CryptoExchangeException(json_decode($request->getBody()->getContents()));

        }

        /**
         * Cast and return.
         */

        return collect(json_decode($request->getBody()->getContents()))->except('status')
            ->map(fn($i) => collect($i)->only('lastTradePrice')->values()->first())
            ->reject(fn($i) => !isset($i[0][0]))
            ->map(fn($value, $key) => str_contains($key, 'IRT') ? (string)($value / 10) : $value);
    }

    /**
     * @return Collection
     * @throws CryptoExchangeException|GuzzleException
     */

    public function getProfile(): Collection
    {
        $request = $this->client(true)->get('users/profile');

        if ($request->getStatusCode() != 200){

            throw new CryptoExchangeException(json_decode($request->getBody()->getContents()));

        }

        return collect(json_decode($request->getBody()->getContents()));
    }

    /**
     * @return Collection
     * @throws GuzzleException|CryptoExchangeException
     */

    // Todo: RLS To IRT.

    public function getOrders(): Collection
    {
        $request = $this->client(true)->post('market/orders/list', [
            'json'  =>  [
                'status'    =>  'all',
                'details'   =>  2,
            ],
        ]);

        if ($request->getStatusCode() != 200){

            throw new CryptoExchangeException(json_decode($request->getBody()->getContents()));

        }

        return collect(collect(json_decode($request->getBody()->getContents()))?->only('orders')->first());
    }

    /**
     * @param string $order
     * @return Collection
     * @throws GuzzleException|CryptoExchangeException
     */

    public function getOrder(string $order): Collection
    {
        $request = $this->client(true)->post('market/orders/status', [
            'json'  =>  [
                'id'    =>  $order
            ],
        ]);

        if ($request->getStatusCode() != 200){

            throw new CryptoExchangeException(json_decode($request->getBody()->getContents()));

        }

        return $this->getCastedOrder(json_decode($request->getBody()->getContents())->order);
    }

    /**
     * @return Collection
     * @throws GuzzleException|CryptoExchangeException
     */

    public function getLoginAttempts(): Collection
    {
        $request = $this->client(true)->get('users/login-attempts');

        if ($request->getStatusCode() != 200){

            throw new CryptoExchangeException(json_decode($request->getBody()->getContents()));

        }

        return collect(json_decode($request->getBody()->getContents()))->only('attempts');
    }

    /**
     * @return Collection
     * @throws GuzzleException|CryptoExchangeException
     */

    public function getWallets(): Collection
    {
        $request = $this->client(true)->post('users/wallets/list');

        if ($request->getStatusCode() != 200){

            throw new CryptoExchangeException(json_decode($request->getBody()->getContents()));

        }

        return collect(json_decode($request->getBody()->getContents()))?->only('wallets');
    }

    /**
     * @param string $market
     * @return Collection
     * @throws CryptoExchangeException
     * @throws GuzzleException
     */

    public function getMarketTrades(string $market): Collection
    {
        /**
         * Make request.
         */

        $request = $this->client()->get('v2/trades/' . $market);

        /**
         * Handle Failure.
         */

        if ($request->getStatusCode() != 200){

            throw new CryptoExchangeException(json_decode($request->getBody()->getContents()));

        }

        /**
         * Cast and return.
         */

        return collect(json_decode($request->getBody()->getContents()));
    }

    /**
     * @param string $market
     * @return Collection
     * @throws CryptoExchangeException
     * @throws GuzzleException
     */

    public function getMarketOrderBook(string $market): Collection
    {
        $request = $this->client()->get('v2/orderbook/' . $market);

        /**
         * Handle Failure.
         */

        if ($request->getStatusCode() != 200){

            throw new CryptoExchangeException(json_decode($request->getBody()->getContents()));

        }

        /**
         * Cast and Return.
         */

        return collect(json_decode($request->getBody()->getContents()));
    }

    /**
     * @param string $market
     * @return Collection
     * @throws CryptoExchangeException
     * @throws GuzzleException
     */

    public function getMarketTicker(string $market): Collection
    {
        /**
         * Cast Market.
         */

        $market = explode('-', $market);

        $request = $this->client()->get('market/stats', [
            'query' =>  [
                'srcCurrency'   =>  $market[0],
                'dstCurrency'   =>  $market[1]
            ],
        ]);

        if ($request->getStatusCode() != 200){

            throw new CryptoExchangeException(json_decode($request->getBody()->getContents()));

        }

        /**
         * Cast and Return.
         */

        return collect(json_decode($request->getBody()->getContents()));
    }

    /**
     * @param string $market
     * @param string $starting_from
     * @param string $ending_at
     * @return Collection
     * @throws CryptoExchangeException
     * @throws GuzzleException
     */

    public function getMarketCandles(string $market, string $starting_from, string $ending_at): Collection
    {
        /**
         * Make Request.
         */

        $request = $this->client()->get('market/udf/history', [
            'query' =>  [
                'symbol'   =>  $market,
                'resolution'    =>  'D',
                'from'  =>  Carbon::parse($starting_from)->unix(),
                'to'    =>  Carbon::parse($ending_at)->unix()
            ],
        ]);

        if ($request->getStatusCode() != 200){

            throw new CryptoExchangeException(json_decode($request->getBody()->getContents()));

        }

        /**
         * Cast and Return.
         */

        return collect(json_decode($request->getBody()->getContents()));
    }

    /**
     * Attention!
     * 1. Nobitex accepts 'RLS' as monetary. So, This function converts any RLS to IRT.
     * 2. This function has been limited to execute LIMIT and MARKET type of orders.

     * @param string $market
     * @param float $quantity
     * @param string $side
     * @param string $type
     * @param float|null $originalPrice
     * @return Collection
     * @throws CryptoExchangeException|GuzzleException
     */

    public function createOrder(string $market, float $quantity, string $side, string $type, ?float $originalPrice = null): Collection
    {
        /**
         * Define Request Body.
         */

        $requestBody = [
            'amount'    =>  $this->getCastedFloat($quantity)
        ];

        /**
         * Validations and data-castings.
         */

        /**
         * Handle Market.
         */

        $market = $this->getMarketExploded($market);

        if ($market[1] === 'IRT'){

            $market[1] = 'RLS';

        }

        $requestBody['srcCurrency'] = $market[0];

        $requestBody['dstCurrency'] = $market[1];

        /**
         * Handle Side.
         */

        if (!in_array($side, ['BUY', 'SELL'])){

            throw new CryptoExchangeException(trans('crypto-exchange::errors.sideNotValid'));

        }

        $requestBody['type'] = $side;

        /**
         * Handle Order Type.
         */

        if (!in_array($type, ['LIMIT', 'MARKET'])){

            throw new CryptoExchangeException(trans('crypto-exchange::errors.typeNotValid'));

        }

        $requestBody['execution'] = $type;

        /**
         * Handle Price.
         */

        if ($originalPrice){

            $requestBody['price'] = $originalPrice;

        }

        /**
         * Lowercase requestBody.
         */

        $requestBody = collect($requestBody)->map(fn($v) => strtolower($v));

        /**
         * Making request.
         */

        $request = $this->client(true)->post('market/orders/add', [
            'json'  =>  $requestBody
        ]);

        if ($request->getStatusCode() != 200){

            throw new CryptoExchangeException(json_decode($request->getBody()->getContents())->message);

        }

        /**
         * Return order.
         */

        return $this->getCastedOrder(json_decode($request->getBody()->getContents())->order);
    }

    /**
     * @param string $order
     * @return Collection
     * @throws CryptoExchangeException
     * @throws GuzzleException
     */

    public function cancelOrder(string $order): Collection
    {
        /**
         * Make request.
         */

        $request = $this->client(true)->post('market/orders/update-status', [
            'json'  =>  [
                'order' =>  $order,
                'status'    =>  'canceled'
            ],
        ]);

        if ($request->getStatusCode() != 200){

            throw new CryptoExchangeException(json_decode($request->getBody()->getContents())->message);

        }

        /**
         * Return response.
         */

        return collect(json_decode($request->getBody()->getContents()));
    }

    /**
     * @param object $order
     * @return Collection
     */

    protected function getCastedOrder(object $order): Collection
    {
        $isRLS = str_ends_with($order->market, '-RLS');

        return collect([
            'id'   =>  $order->id,
            'market'    =>  $isRLS ? str_replace('RLS', 'IRT', $order->market) : $order->market,
            'type'  =>  $order->execution,
            'side'  =>  $order->type,
            'original_quantity' =>  $this->getCastedFloat($order->amount),
            'executed_quantity' =>  $this->getCastedFloat($order->matchedAmount),
            'cumulative_quote_quantity' =>  $this->getCastedFloat($isRLS ? $order->totalPrice / 10 : $order->totalPrice),
            'fill_percentage'   =>  $this->getCastedFloat($order->matchedAmount / $order->amount * 100, 2),
            'original_price'   =>   is_numeric($order->price) ? $this->getCastedFloat($isRLS ? $order->price / 10 : $order->price) : null,
            'executed_price'    =>  $this->getCastedFloat($isRLS ? $order->averagePrice / 10 : $order->averagePrice),
            'wage_quantity' =>  $this->getCastedFloat($order->type === 'sell' && $isRLS ? $order->fee / 10 : $order->fee),
            'created_at'    =>  Carbon::parse($order->created_at)->toIso8601String()
        ])->map(fn($v) => (!is_numeric($v) && !is_null($v)) ? strtoupper($v) : $v);
    }
}
