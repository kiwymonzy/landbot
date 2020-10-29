<?php

namespace App\Http\Controllers\Dashboard;

use App\Library\Utils\ResponseUtil;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends BaseController
{
    public function requests(Request $request)
    {
        $range = $this->parseDates($request);
        $data = Client::whereBetween('created_at', [
                $range['start'],
                $range['end']
            ])->select(['id', 'company'])
            ->withCount(['statistics'])
            ->get()
            ->sortByDesc('statistics_count')
            ->values();

        return ResponseUtil::makeResponse('Client statistics', $data);
    }

    public function mutations(Request $request)
    {
        $range = $this->parseDates($request);
        $data = Client::whereBetween('created_at', [
                $range['start'],
                $range['end']
            ])->select(['id', 'company'])
            ->withCount(['statusMutations'])
            ->get()
            ->sortByDesc('status_mutations_count')
            ->values();

        return ResponseUtil::makeResponse('Client mutations', $data);
    }

    public function count(Request $request)
    {
        $range = $this->parseDates($request);
        $data = Client::whereBetween('created_at', [
                $range['start'],
                $range['end']
            ])->get();

        return ResponseUtil::makeResponse('Client count', [
            'total' => $data->count(),
        ]);
    }
}
