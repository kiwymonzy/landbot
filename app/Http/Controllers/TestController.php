<?php

namespace App\Http\Controllers;

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
                ->fetch()
                ->getCampaigns()
                ;
            dd($service);
        }
    }
}
