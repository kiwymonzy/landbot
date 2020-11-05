<?php

namespace App\Http\Controllers;

use App\Library\WildJar\WildJar;
use App\Models\Call;
use App\Models\Client;
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

        $calls = $this->fetchCalls($wjAccounts, $dates)
            ->map(function($call) {
                return [
                    'name' => $call['dateStartLocal'],
                    'link' => $call['audio']
                ];
            })
            ->filter(function($call) {
                return !is_null($call['link']);
            })
            ->sortBy('name')
            ->values();

        // $res = [
        //     'answered' => intval($calls['answeredTot']),
        //     'missed' => $calls['missedTot'] + $calls['abandonedTot'],
        // ];

        // $this->makeModel($res, $dates, $fsAccount);

        return $this->sendResponse('Success!', $calls);
    }

    private function fetchCalls($accounts, $dates)
    {
        $data = [
            'account' => $accounts->join(','),
            'datefrom' => $dates['start'],
            'dateto' => $dates['end'],
            'timezone' => 'Australia/Sydney',
        ];

        return $this->client->call()->index($data);
    }

    /**
     * Parse WildJar sub accounts from ID
     *
     * @param \Illuminate\Support\Collection $account
     * @return \Illuminate\Support\Collection
     */
    private function fetchWJSubAccounts($account)
    {
        $allAccounts = $this->client->account()->all();

        $allAccountIds = $allAccounts->filter(function($q) use ($account) {
            return $q['father'] == $account;
        })->pluck('id');

        return $allAccountIds->push($account);
    }

    private function dateMapper($index)
    {
        $start_base = Carbon::today();
        $end_base = Carbon::today();
        switch ($index) {
            case 1:
                $start = $start_base->startOfDay();
                $end = $end_base->endOfDay();
                $name = 'Today';
                break;
            case 2:
                $start = $start_base->subDay()->startOfDay();
                $end = $end_base->subDay()->endOfDay();
                $name = 'Yesterday';
                break;
            case 3:
                $start = $start_base->startofWeek();
                $end = $end_base->endofWeek();
                $name = 'This Week';
                break;
            case 4:
                $start = $start_base->subWeek()->startOfWeek();
                $end = $end_base->subWeek()->endOfWeek();
                $name = 'Last Week';
                break;
            case 5:
                $start = $start_base->startOfMonth();
                $end = $end_base->endOfMonth();
                $name = 'This Month';
                break;
            case 6:
                $start = $start_base->subMonth()->startOfMonth();
                $end = $end_base->subMonth()->endOfMonth();
                $name = 'Last Month';
                break;
            default:
                $start = $start_base->startOfDay();
                $end = $end_base->endOfDay();
                $name = 'Today';
                break;
        }
        return [
            'start' => $start->format('Y-m-d\TH:i:s'),
            'end' => $end->format('Y-m-d\TH:i:s'),
            'name' => $name,
        ];
    }

    /**
     * Parse WildJar ID from FreshSales accounts
     *
     * @param \Illuminate\Support\Collection $account
     * @return array
     */
    private function parseWildJarId($account)
    {
        return $account['custom_field']['cf_wildjar_id'];
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

    private function makeModel($data, $dates, $account)
    {
        $call = Call::make([
            'answered'  => $data['answered'],
            'missed'    => $data['missed'],
            'date_name' => $dates['name'],
            'date_from' => $dates['start'],
            'date_to'   => $dates['end'],
        ]);
        $call->client()->associate(
            Client::firstWhere('freshsales_id', $account['id'])
        );
        $call->save();
    }
}
