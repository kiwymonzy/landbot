<?php

namespace App\Http\Controllers\Dashboard;

use App\Library\Utils\ResponseUtil;
use App\Models\Statistic;
use Illuminate\Http\Request;

class StatisticsController extends BaseController
{
    public function count(Request $request)
    {
        $range = $this->parseDates($request);
        $stats = Statistic::whereBetween('created_at', [
                $range['start'],
                $range['end']
            ])->get();

        return ResponseUtil::makeResponse('Statistics count', [
            'total' => $stats->count(),
        ]);
    }
}
