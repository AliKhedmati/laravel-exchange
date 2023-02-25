<?php

namespace AliKhedmati\Exchange;

use Illuminate\Support\ServiceProvider;

class ExchangeServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            path: __DIR__. '/../config/exchange.php',
            key: 'exchange'
        );

        $this->app->singleton('exchange', fn($app) => new ExchangeManager($app));
    }

    public function boot()
    {
        $this->publishes(
            paths: [
                __DIR__. '/../config/exchange.php'  =>  config_path('exchange.php')
            ],
            groups: 'config'
        );

        $this->loadTranslationsFrom(
            path: __DIR__. '/../lang',
            namespace: 'exchange'
        );
    }
}