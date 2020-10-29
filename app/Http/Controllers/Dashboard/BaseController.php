<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BaseController extends Controller
{
    /**
     * Parse request dates to set
     * from as start of day and
     * to as end of day
     *
     * @param Request $request
     * @return Carbon[]
     */
    protected function parseDates(Request $request)
    {
        $start_base = Carbon::today();
        $end_base = Carbon::today();

        switch ($request->date) {
            case 1:
                // Today
                $start = $start_base->startOfDay();
                $end = $end_base->endOfDay();
                break;
            case 2:
                // Yesterday
                $start = $start_base->subDay()->startOfDay();
                $end = $end_base->subDay()->endOfDay();
                break;
            case 3:
                // This Week
                $start = $start_base->startofWeek();
                $end = $end_base->endofWeek();
                break;
            case 4:
                // Last Week
                $start = $start_base->subWeek()->startOfWeek();
                $end = $end_base->subWeek()->endOfWeek();
                break;
            case 5:
                // This Month
                $start = $start_base->startOfMonth();
                $end = $end_base->endOfMonth();
                break;
            case 6:
                // Last Month
                $start = $start_base->subMonth()->startOfMonth();
                $end = $end_base->subMonth()->endOfMonth();
                break;
            case 7:
                // All time
                $start = $start_base->startOfMillennium();
                $end = $end_base->endOfMillennium();
                break;
            default:
                // All time
                $start = $start_base->startOfMillennium();
                $end = $end_base->endOfMillennium();
                break;
        }

        return [
            'start' => $start,
            'end' => $end,
        ];
    }
}
