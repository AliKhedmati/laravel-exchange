<?php

namespace AliKhedmati\Exchange\Drivers;

use AliKhedmati\Exchange\Exceptions\ExchangeException;
use AliKhedmati\Exchange\Utils\Utils;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class Nobitex
{
    /**
     * Nobitex Class Constructor.
     */

    public function __construct(private readonly string $restApiBase, private readonly ?string $apiKey = null) {}

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

        if ($isAuthenticated && $this->apiKey){

            $headers['Authorization'] = 'Token ' . $this->apiKey;

        }

        return new Client([
            'base_uri' => $this->restApiBase,
            'headers' =>    $headers,
            'http_errors' => false
        ]);
    }

    /**
     * Tested.
     * @return Collection
     * @throws GuzzleException|ExchangeException
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

            throw new ExchangeException(json_decode($request->getBody()->getContents())->message);

        }

        /**
         * Cast and return.
         */

        return collect(json_decode($request->getBody()->getContents()))
            ->except(['status'])
            ->map(fn($i) => collect($i)->only('lastTradePrice')->values()->first())
            ->reject(fn($value) => !isset($value[0][0]))
            ->mapWithKeys(function ($price, $market){
                return [
                    Utils::getHyphenatedMarketName($market) => Utils::castFloat(str_contains($market, 'IRT') ? ($price / 10) : $price)
                ];
            });
    }

    /**
     * @return Collection
     * @throws ExchangeException|GuzzleException
     */

    public function getProfile(): Collection
    {
        $request = $this->client(true)->get('users/profile');

        if ($request->getStatusCode() != 200){

            throw new ExchangeException(json_decode($request->getBody()->getContents())->message);

        }

        return collect(json_decode($request->getBody()->getContents()));
    }

    /**
     * @return Collection
     * @throws GuzzleException|ExchangeException
     */

    public function getOrders(): Collection
    {
        $request = $this->client(true)->post('market/orders/list', [
            'json'  =>  [
                'status'    =>  'all',
                'details'   =>  2,
            ],
        ]);

        if ($request->getStatusCode() != 200){

            throw new ExchangeException(json_decode($request->getBody()->getContents())->message);

        }

        return collect(collect(json_decode($request->getBody()->getContents()))?->only('orders')->first());
    }

    /**
     * @param string $order
     * @return Collection
     * @throws GuzzleException|ExchangeException
     */

    public function getOrder(string $order): Collection
    {
        $request = $this->client(true)->post('market/orders/status', [
            'json'  =>  [
                'id'    =>  $order
            ],
        ]);

        if ($request->getStatusCode() != 200){

            throw new ExchangeException(json_decode($request->getBody()->getContents())->message);

        }

        return $this->getCastedOrder(json_decode($request->getBody()->getContents())->order);
    }

    /**
     * @return Collection
     * @throws GuzzleException|ExchangeException
     */

    public function getLoginAttempts(): Collection
    {
        $request = $this->client(true)->get('users/login-attempts');

        if ($request->getStatusCode() != 200){

            throw new ExchangeException(json_decode($request->getBody()->getContents())->message);

        }

        return collect(json_decode($request->getBody()->getContents()))->only('attempts');
    }

    /**
     * @return Collection
     * @throws GuzzleException|ExchangeException
     */

    public function getWallets(): Collection
    {
        $request = $this->client(true)->post('users/wallets/list');

        if ($request->getStatusCode() != 200){

            throw new ExchangeException(json_decode($request->getBody()->getContents())->message);

        }

        return collect(json_decode($request->getBody()->getContents()))?->only('wallets');
    }

    /**
     * Tested.
     * @param string $market
     * @return Collection
     * @throws ExchangeException
     * @throws GuzzleException
     */

    public function getMarketTrades(string $market): Collection
    {
        /**
         * Make request.
         */

        $request = $this->client()->get('v2/trades/' . Utils::getConcatenatedMarketName($market));

        /**
         * Handle Failure.
         */

        if ($request->getStatusCode() != 200){

            throw new ExchangeException(json_decode($request->getBody()->getContents())->message);

        }

        /**
         * Cast and return.
         */

        return collect(json_decode($request->getBody()->getContents())->trades)->map(function ($item) use ($market){
            $item->price = Utils::castFloat($item->price * (str($market)->contains('IRT') ? .1 : 1));
            return $item;
        });
    }

    /**
     * Tested.
     * @param string $market
     * @return Collection
     * @throws ExchangeException
     * @throws GuzzleException
     */

    public function getMarketOrderBook(string $market): Collection
    {
        $request = $this->client()->get('v2/orderbook/' . Utils::getConcatenatedMarketName($market));

        /**
         * Handle Failure.
         */

        if ($request->getStatusCode() != 200){

            throw new ExchangeException(json_decode($request->getBody()->getContents())->message);

        }

        /**
         * Cast and Return.
         */

        return collect(json_decode($request->getBody()->getContents()))->only(['bids', 'asks'])->map(function ($item, $list) use ($market){
            return collect($item)->map(function ($item) use ($market){
                $item[0] = Utils::castFloat($item[0] * (str($market)->contains('IRT') ? .1 : 1));
                return $item;
            });
        });
    }

    /**
     * Tested.
     * @param string $market
     * @return Collection
     * @throws ExchangeException
     * @throws GuzzleException
     */

    public function getMarketTicker(string $market): Collection
    {
        $market = Utils::getHyphenatedMarketName($market);

        $marketExploded = explode('-', $market);

        $request = $this->client()->get('market/stats', [
            'query' =>  [
                'srcCurrency'   =>  $marketExploded[0],
                'dstCurrency'   =>  $marketExploded[1]
            ],
        ]);

        if ($request->getStatusCode() != 200){

            throw new ExchangeException(json_decode($request->getBody()->getContents())->message);

        }

        $data = collect(collect(json_decode($request->getBody()->getContents())->stats)->flatten()->first());

        /**
         * Cast and Return.
         */

        return $data->except(['isClosed', 'dayChange', 'volumeSrc'])->map(function ($item) use ($market){
            return Utils::castFloat(str($market)->contains('IRT') ? $item * .1 : $item);
        })->merge($data->only(['isClosed', 'dayChange', 'volumeSrc']));
    }

    /**
     * Tested.
     * @param string $market
     * @param string $starting_from
     * @param string $ending_at
     * @param string $resolution
     * @return Collection
     * @throws ExchangeException
     * @throws GuzzleException
     */

    public function getMarketCandles(string $market, string $starting_from, string $ending_at, string $resolution): Collection
    {
        /**
         * Make Request.
         */

        $request = $this->client()->get('market/udf/history', [
            'query' =>  [
                'symbol'   =>  Utils::getConcatenatedMarketName($market),
                'resolution'    =>  $resolution,
                'from'  =>  Carbon::parse($starting_from)->unix(),
                'to'    =>  Carbon::parse($ending_at)->unix()
            ],
        ]);

        if ($request->getStatusCode() != 200){

            throw new ExchangeException(json_decode($request->getBody()->getContents())->message);

        }

        /**
         * Cast and Return.
         */

        return collect(json_decode($request->getBody()->getContents()))->except(['s']);
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
     * @throws ExchangeException|GuzzleException
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

            throw new ExchangeException(trans('crypto-exchange::errors.sideNotValid'));

        }

        $requestBody['type'] = $side;

        /**
         * Handle Order Type.
         */

        if (!in_array($type, ['LIMIT', 'MARKET'])){

            throw new ExchangeException(trans('crypto-exchange::errors.typeNotValid'));

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

            throw new ExchangeException(json_decode($request->getBody()->getContents())->message);

        }

        /**
         * Return order.
         */

        return $this->getCastedOrder(json_decode($request->getBody()->getContents())->order);
    }

    /**
     * @param string $order
     * @return Collection
     * @throws ExchangeException
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

            throw new ExchangeException(json_decode($request->getBody()->getContents())->message);

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
