<?php

namespace App\Http\Controllers\Dashboard;

use App\Library\Utils\ResponseUtil;
use App\Models\BudgetMutation;
use App\Models\StatusMutation;
use Illuminate\Http\Request;

class GoogleAdsController extends BaseController
{
    public function budgetIncreaseCount(Request $request)
    {
        $range = $this->parseDates($request);
        $mutations = BudgetMutation::whereBetween('created_at', [
                $range['start'],
                $range['end']
            ])->get();

        return ResponseUtil::makeResponse('Budget mutation count', [
            'total' => $mutations->count(),
        ]);
    }

    public function pauseCount(Request $request)
    {
        $range = $this->parseDates($request);
        $mutations = StatusMutation::whereBetween('created_at', [
                $range['start'],
                $range['end']
            ])->where('status_new', StatusMutation::PAUSED)
            ->get();

        return ResponseUtil::makeResponse('Budget mutation count', [
            'total' => $mutations->count(),
        ]);
    }
}
