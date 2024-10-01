<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProfitList extends Command
{
    protected $signature = 'price:profit';
    protected $description = 'Forms a list of currency pairs with % profit between the minimum price on exchange 1 and the maximum price on exchange 2';

    private $analyzePrice;

    public function __construct(AnalyzePrice $analyzePrice)
    {
        parent::__construct();
        $this->analyzePrice = $analyzePrice;
    }

    public function handle()
    {
        $commonPairs = $this->analyzePrice->getCommonPairs();

        $profitList = [];

        foreach ($commonPairs as $pair) {
            $prices = $this->analyzePrice->analyzePriceForPair($pair);

            if ($prices) {
                $minPrice = $prices['min_price']['price'];
                $maxPrice = $prices['max_price']['price'];

                $profitPercentage = (($maxPrice - $minPrice) / $minPrice) * 100;

                $profitList[] = [
                    'pair' => $pair,
                    'min_exchange' => $prices['min_price']['exchange'],
                    'max_exchange' => $prices['max_price']['exchange'],
                    'min_price' => $minPrice,
                    'max_price' => $maxPrice,
                    'profit_percentage' => $profitPercentage,
                ];
            }
        }

        $this->table(
            ['Currency Pair', 'Exchange (min)', 'Min Price', 'Exchange (max)', 'Max Price', '% Profit'],
            array_map(function ($item) {
                return [
                    $item['pair'],
                    $item['min_exchange'],
                    $item['min_price'],
                    $item['max_exchange'],
                    $item['max_price'],
                    number_format($item['profit_percentage'], 2) . '%',
                ];
            }, $profitList)
        );
    }
}
