<?php

namespace App\Http\Controllers;

use LaravelAds\LaravelAds;

class TestController extends Controller
{
    public function index()
    {
        $ids = [
            '135091877',
        ];

        $dates = [
            'from' => now()->subMonths(1)->startOfMonth()->format('Y-m-d'),
            'to' => now()->subMonths(1)->endOfMonth()->format('Y-m-d'),
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
            dd($service->sum('cost'));
        }
    }
}
