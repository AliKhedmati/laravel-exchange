<?php

namespace AliKhedmati\Exchange\Utils;

use Illuminate\Support\Str;

class Utils
{
    /**
     * @param float $float
     * @param int $decimals
     * @return string
     */

    public static function castFloat(float $float, int $decimals = 6): string
    {
        return number_format($float, $decimals, '.', '');
    }

    /**
     * @param string $marketName
     * @return string
     */

    public static function getHyphenatedMarketName(string $marketName): string
    {
        /**
         * Remove everything except letters.
         */

        $marketName = preg_replace('/\PL/u', '', $marketName);

        $castedMarket = '';

        foreach (['IRT', 'USDT', 'BTC', 'ETH'] as $dstMarket){

            if (str($marketName)->endsWith($dstMarket)){

                $castedMarket = str($marketName)->before($dstMarket)->append('-'.$dstMarket);

                break;
            }

        }

        return $castedMarket;
    }

    /**
     * @param string $marketName
     * @return string
     */

    public static function getConcatenatedMarketName(string $marketName): string
    {
        $replaceable = ['-', '_', ' ', '.'];

        return str($marketName)->whenContains($replaceable, fn($str) => $str->replace($replaceable, ''));
    }
}