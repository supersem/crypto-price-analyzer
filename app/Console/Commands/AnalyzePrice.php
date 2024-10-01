<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;

class AnalyzePrice extends Command
{
    protected $signature = 'price:analyze {pair}';
    protected $description = 'Fetch the lowest and highest price of a currency pair from different exchanges';

    private $httpClient;

    public function __construct(Client $httpClient)
    {
        parent::__construct();
        $this->httpClient = $httpClient;
    }

    public function handle()
    {
        $pair = $this->argument('pair');
        $prices = $this->analyzePriceForPair($pair);

        if ($prices) {
            $this->info("Price analysis for pair: {$pair}");
            $this->info("Minimum price: {$prices['min_price']['price']} on exchange {$prices['min_price']['exchange']}");
            $this->info("Maximum price: {$prices['max_price']['price']} on exchange {$prices['max_price']['exchange']}");
        } else {
            $this->error("Unable to fetch prices for pair {$pair}.");
        }
    }

    public function getCommonPairs()
    {
        $binancePairs = $this->getBinancePairs();
        $jbexPairs = $this->getJbexPairs();
        $poloniexPairs = $this->getPoloniexPairs();
        $bybitPairs = $this->getBybitPairs();
        $whitebitPairs = $this->getWhitebitPairs();

        $commonPairs = array_intersect($binancePairs, $jbexPairs, $poloniexPairs, $bybitPairs, $whitebitPairs);

        return $commonPairs;
    }

    public function analyzePriceForPair($pair)
    {
        $commonPairs = $this->getCommonPairs();

        if (in_array($pair, $commonPairs)) {
            $binancePrice = $this->getBinancePrice($pair);
            $jbexPrice = $this->getJbexPrice($pair);
            $poloniexPrice = $this->getPoloniexPrice($pair);
            $bybitPrice = $this->getBybitPrice($pair);
            $whitebitPrice = $this->getWhitebitPrice($pair);

            $prices = [
                'binance' => $binancePrice,
                'jbex' => $jbexPrice,
                'poloniex' => $poloniexPrice,
                'bybit' => $bybitPrice,
                'whitebit' => $whitebitPrice,
            ];

            $filteredPrices = array_filter($prices, function ($price) {
                return $price !== null;
            });

            if (empty($filteredPrices)) {
                $this->error("No available prices for the pair {$pair}.");
                return null;
            }

            $minPrice = min($filteredPrices);
            $maxPrice = max($filteredPrices);

            $minExchange = array_search($minPrice, $filteredPrices);
            $maxExchange = array_search($maxPrice, $filteredPrices);

            return [
                'min_price' => [
                    'price' => $minPrice,
                    'exchange' => $minExchange,
                ],
                'max_price' => [
                    'price' => $maxPrice,
                    'exchange' => $maxExchange,
                ],
            ];
        } else {
            $this->error("The pair {$pair} is not supported on all exchanges.");
            return null;
        }
    }

    private function getBinancePairs()
    {
        try {
           $response = $this->httpClient->get("https://api.binance.com/api/v3/exchangeInfo");
            $data = json_decode($response->getBody(), true);
            if (!isset($data['symbols'])) {
                $this->error("Binance: Incorrect response from the API");
                return [];
            }

            $pairs = array_map(function ($symbolInfo) {
                return $symbolInfo['baseAsset'] . '/' . $symbolInfo['quoteAsset'];
            }, $data['symbols']);

            return $pairs;
        } catch (\Exception $e) {
            $this->error("Binance: error while retrieving currency pairs - " . $e->getMessage());
            return [];
        }
    }

    private function getJbexPairs()
    {
        try {
            $response = $this->httpClient->get('https://api.bitget.com/api/spot/v1/public/products');

            $data = json_decode($response->getBody(), true);

            if (!isset($data['data']) || !is_array($data['data'])) {
                $this->error("JBEX: Incorrect response from the API");
                return [];
            }

            $pairs = array_map(function ($symbolInfo) {
                return $symbolInfo['baseCoin'] . '/' . $symbolInfo['quoteCoin'];
            }, $data['data']);

            return $pairs;
        } catch (\Exception $e) {
            $this->error("JBEX: error while retrieving currency pairs - " . $e->getMessage());
            return [];
        }
    }

    private function getPoloniexPairs()
    {
        try {
            $response = $this->httpClient->get('https://api.poloniex.com/markets');

            $data = json_decode($response->getBody(), true);

            if (!is_array($data)) {
                $this->error("Poloniex: Incorrect response from the API");
                return [];
            }

            $pairs = array_map(function ($symbolInfo) {
                return $symbolInfo['baseCurrencyName'] . '/' . $symbolInfo['quoteCurrencyName'];
            }, $data);

            return $pairs;
        } catch (\Exception $e) {
            $this->error("Poloniex: error while retrieving currency pairs - " . $e->getMessage());
            return [];
        }
    }

    private function getBybitPairs()
    {
        try {
            $response = $this->httpClient->get('https://api.bybit.com/v2/public/symbols');

            $data = json_decode($response->getBody(), true);

            if (!isset($data['result']) || !is_array($data['result'])) {
                $this->error("Bybit: Incorrect response from the API");
                return [];
            }

            $pairs = array_map(function ($symbolInfo) {
                return $symbolInfo['base_currency'] . '/' . $symbolInfo['quote_currency'];
            }, $data['result']);
            return $pairs;
        } catch (\Exception $e) {
            $this->error("Bybit: error while retrieving currency pairs - " . $e->getMessage());
            return [];
        }
    }

    private function getWhitebitPairs()
    {
        try {
            $response = $this->httpClient->get('https://whitebit.com/api/v4/public/markets');

            $data = json_decode($response->getBody(), true);

            if (!is_array($data)) {
                $this->error("Whitebit: Incorrect response from the API");
                return [];
            }

            $pairs = array_map(function ($symbolInfo) {
                return $symbolInfo['stock'] . '/' . $symbolInfo['money'];
            }, $data);

            return $pairs;
        } catch (\Exception $e) {
            $this->error("Whitebit: error while retrieving currency pairs - " . $e->getMessage());
            return [];
        }
    }


    private function getBinancePrice($pair)
    {
        try {
            $response = $this->httpClient->get("https://api.binance.com/api/v3/ticker/price", [
                'query' => ['symbol' => $this->formatPairForBinance($pair)]
            ]);

            $data = json_decode($response->getBody(), true);
            return $data['price'] ?? null;
        } catch (\Exception $e) {
            $this->error("Binance: error while retrieving the price.");
            return null;
        }
    }

    private function getJbexPrice($pair)
    {
        try {
            $response = $this->httpClient->get("https://api.bitget.com/api/v2/mix/market/symbol-price", [
                'query' => ['productType'=> 'usdt-futures', 'symbol' => $this->formatPairForJbex($pair)]
            ]);

            $data = json_decode($response->getBody(), true);
            return $data['data']['0']['price'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getPoloniexPrice($pair)
    {
        try {
            $formattedPair = $this->formatPairForPoloniex($pair);
            $response = $this->httpClient->get("https://api.poloniex.com/markets/{$formattedPair}/price");

            $data = json_decode($response->getBody(), true);
            return $data['price'] ?? null;
        } catch (\Exception $e) {
            $this->error("Poloniex: error while retrieving the price.");
            return null;
        }
    }

    private function getBybitPrice($pair)
    {
        try {
            $response = $this->httpClient->get("https://api.bybit.com/v5/market/tickers", [
            'query' => ['category'=> 'spot', 'symbol' => $this->formatPairForBybit($pair)]
            ]);

            $data = json_decode($response->getBody(), true);

            if (isset($data['result']) && isset($data['result']['list']['0']['lastPrice'])) {
                return $data['result']['list']['0']['lastPrice']; // Отримуємо поточну ціну
            } else {
                return null;
            }

        } catch (\Exception $e) {
            $this->error("Bybit: error while retrieving the price.");
            return null;
        }
    }

    private function getWhitebitPrice($pair)
    {
        try {
            $response = $this->httpClient->get("https://whitebit.com/api/v1/public/tickers");

            $data = json_decode($response->getBody(), true);
            $formattedPair = $this->formatPairForWhitebit($pair);

            if (isset($data['result'][$formattedPair]['ticker']['last'])) {
                return $data['result'][$formattedPair]['ticker']['last'];
            } else {
                return null;
            }

        } catch (\Exception $e) {
            $this->error("Whitebit: error while retrieving the price.");
            return null;
        }
    }

    private function formatPairForBinance($pair)
    {
        return str_replace('/', '', $pair);
    }

    private function formatPairForJbex($pair)
    {
        return str_replace('/', '', $pair);
    }

    private function formatPairForPoloniex($pair)
    {
        return str_replace('/', '_', $pair);
    }

    private function formatPairForBybit($pair)
    {
        return str_replace('/', '', $pair);
    }

    private function formatPairForWhitebit($pair)
    {
        return str_replace('/', '_', $pair);
    }
}
