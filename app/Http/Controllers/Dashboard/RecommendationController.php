<?php

namespace App\Http\Controllers\Dashboard;

use App\Library\Utils\ResponseUtil;
use App\Models\BudgetRecommendation;
use Illuminate\Http\Request;

class RecommendationController extends BaseController
{
    public function count(Request $request)
    {
        $range = $this->parseDates($request);
        $recommendations = BudgetRecommendation::whereBetween('created_at', [
                $range['start'],
                $range['end']
            ])->get();

        return ResponseUtil::makeResponse('Budget recommendations', [
            'total' => $recommendations->count(),
        ]);
    }
}
