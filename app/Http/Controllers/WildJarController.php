<?php

namespace App\Http\Controllers;

use App\Library\WildJar\WildJar;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WildJarController extends Controller
{
    public function __construct()
    {
        $this->client = new WildJar;
    }

    public function calls(Request $request)
    {
        $fsAccount = $this->fetchAccount($request->phone);
        $wjAccount = $this->parseWildJarId($fsAccount);

        $wjAccounts = $this->fetchWJSubAccounts($wjAccount);

        $dates = $this->dateMapper($request->date);
        $calls = $this->client->summary()->filter([
            'account' => $wjAccounts->join(','),
            'datefrom' => $dates['start'],
            'dateto' => $dates['end'],
        ])['summary'];

        $res = [
            'answered' => intval($calls['answeredTot']),
            'missed' => $calls['missedTot'] + $calls['abandonedTot'],
        ];

        return $this->sendResponse('Success!', $res);
    }

    public function dateMapper($index)
    {
        switch ($index) {
            case 1:
                $start = Carbon::today()->startOfDay();
                $end = Carbon::today()->endOfDay();
                break;
            case 2:
                $start = Carbon::today()->subDay()->startOfDay();
                $end = Carbon::today()->subDay()->endOfDay();
                break;
            case 3:
                $start = Carbon::today()->startofWeek();
                $end = Carbon::today()->endofWeek();
                break;
            case 4:
                $start = Carbon::today()->subWeek()->startOfWeek();
                $end = Carbon::today()->subWeek()->endOfWeek();
                break;
            case 5:
                $start = Carbon::today()->startOfMonth();
                $end = Carbon::today()->endOfMonth();
                break;
            case 6:
                $start = Carbon::today()->subMonth()->startOfMonth();
                $end = Carbon::today()->subMonth()->endOfMonth();
                break;
            default:
                $start = Carbon::today()->startOfDay();
                $end = Carbon::today()->endOfDay();
                break;
        }
        return [
            'start' => $start->format('Y-m-d\TH:i:s'),
            'end' => $end->format('Y-m-d\TH:i:s'),
        ];
    }

    /**
     * Parse WildJar ID from FreshSales accounts
     *
     * @param \Illuminate\Support\Collection $account
     * @return array
     */
    public function parseWildJarId($account)
    {
        return $account['custom_field']['cf_wildjar_id'];
    }

    /**
     * Parse WildJar sub accounts from ID
     *
     * @param \Illuminate\Support\Collection $account
     * @return \Illuminate\Support\Collection
     */
    public function fetchWJSubAccounts($account)
    {
        $allAccounts = $this->client->account()->all();

        $allAccountIds = $allAccounts->filter(function($q) use ($account) {
            return $q['father'] == $account;
        })->pluck('id');

        return $allAccountIds->push($account);
    }
}
