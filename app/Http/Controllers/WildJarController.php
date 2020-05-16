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
        $fsAccount = $this->fetchAccount($request->phone)['sales_account'];

        if (!$this->accountIsValid($fsAccount))
            abort(403, 'This feature is not enabled on your account');

        $wjAccount = $this->parseWildJarId($fsAccount);

        $wjAccounts = $this->fetchWJSubAccounts($wjAccount);

        $dates = $this->dateMapper($request->date);
        $data = [
            'account' => $wjAccounts->join(','),
            'datefrom' => $dates['start'],
            'dateto' => $dates['end'],
            'timezone' => 'Australia/Sydney',
        ];
        $calls = $this->client->summary()->filter($data)['summary'];

        $res = [
            'answered' => intval($calls['answeredTot']),
            'missed' => $calls['missedTot'] + $calls['abandonedTot'],
        ];

        return $this->sendResponse('Success!', $res);
    }

    public function dateMapper($index)
    {
        $start_base = Carbon::today();
        $end_base = Carbon::today();
        switch ($index) {
            case 1:
                $start = $start_base->startOfDay();
                $end = $end_base->endOfDay();
                break;
            case 2:
                $start = $start_base->subDay()->startOfDay();
                $end = $end_base->subDay()->endOfDay();
                break;
            case 3:
                $start = $start_base->startofWeek();
                $end = $end_base->endofWeek();
                break;
            case 4:
                $start = $start_base->subWeek()->startOfWeek();
                $end = $end_base->subWeek()->endOfWeek();
                break;
            case 5:
                $start = $start_base->startOfMonth();
                $end = $end_base->endOfMonth();
                break;
            case 6:
                $start = $start_base->subMonth()->startOfMonth();
                $end = $end_base->subMonth()->endOfMonth();
                break;
            default:
                $start = $start_base->startOfDay();
                $end = $end_base->endOfDay();
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

    /**
     * Check if account has feature enabled
     *
     * @param array $account
     * @return bool
     */
    private function accountIsValid($account)
    {
        return isset($account['custom_field']['cf_wildjar_id']);
    }
}
