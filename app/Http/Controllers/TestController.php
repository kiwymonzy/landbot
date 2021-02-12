<?php

namespace App\Http\Controllers;

use App\Library\GoogleAds\GoogleAds;
use Google\AdsApi\AdWords\v201809\cm\CampaignService;
use Google\AdsApi\AdWords\v201809\cm\DateRange;
use Google\AdsApi\AdWords\v201809\cm\Selector;
use LaravelAds\LaravelAds;

class TestController extends Controller
{
    public function index()
    {
        $ids = [
            'F104M6TL',
        ];

        $dates = [
            'from' => now()->subMonth()->startOfMonth()->format('Y-m-d'),
            'to' => now()->subMonth()->endOfMonth()->format('Y-m-d'),
        ];

        $spend = $this->fetchStats($ids, $dates);

        dd($spend);
    }

    private function fetchStats($ids, $dates)
    {
        foreach ($ids as $id) {
            $service = LaravelAds::bingAds()
                ->with($id)
                ->reports($dates['from'], $dates['to'])
                ->getAccountReport();
            dd($service);
        }
    }
}
