<?php

namespace App\Http\Controllers;

use App\Library\WildJar\WildJar;
use App\Models\Client;
use App\Models\Recording;
use Carbon\Carbon;
use ErrorException;
use Illuminate\Http\Request;

class WildJarController extends Controller
{
    // Types
    const ANSWERED = 1;
    const UNDER_30 = 2;
    const MISSED = 3;

    // Durations
    const TODAY = 1;
    const YESTERDAY = 2;
    const THIS_WEEK = 3;
    const LAST_WEEK = 4;
    const THIS_MONTH = 5;
    const LAST_MONTH = 6;

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

        $date = $this->dateMapper($request->date);
        $type = $this->typeMapper($request->type);

        $calls = $this->fetchCalls($wjAccounts, $date, $type)
            ->map(function($call) {
                $time = Carbon::parse($call['dateStartLocal']);

                $web = $call['web'];
                $source = str_replace(['(', ')'], '', $web['source'] ?? 'unknown');
                $caller = $call['caller'];

                return [
                    'name' => $time->format('hA') . ' ' . $source . ' ' . $caller,
                    'link' => $call['audio'],
                    'time' => $time,
                ];
            })
            ->sortBy('time')
            ->values();
        $records = rtrim($calls->reduce(function ($res, $call) {
            return $res . $call['name'] . "\n";
        }));

        $res = [
            'recordings' => $calls,
            'records' => $records,
        ];

        $this->makeModel($res, $date, $type, $fsAccount);

        return $this->sendResponse('Success!', $res);
    }

    /**
     * Fetch calls
     *
     * @param \Illuminate\Support\Collection $accounts
     * @param array $dates
     * @param array $types
     * @return \Illuminate\Support\Collection
     */
    private function fetchCalls($accounts, $dates, $types)
    {
        $data = array_merge([
            'account' => $accounts->join(','),
            'datefrom' => $dates['start'],
            'dateto' => $dates['end'],
            'timezone' => 'Australia/Sydney',
        ], $types['data']);

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
            try {
                return $q['father'] == $account;
            } catch (ErrorException $e) {
                return false;
            }
        })->pluck('id');

        return $allAccountIds->push($account);
    }

    private function typeMapper($index)
    {
        switch($index) {
            case self::ANSWERED:
                $name = 'Answered';
                $data = [
                    'status' => 'answered',
                ];
                break;
            case self::UNDER_30:
                $name = 'Under 30 Seconds';
                $data = [
                    'status' => 'answered',
                    'durationMin' => 0,
                    'durationMax' => 30,
                ];
                break;
            case self::MISSED:
                $name = 'Missed';
                $data = [
                    'status' => 'missed',
                ];
                break;
            default:
                $data = [
                    'status' => 'answered',
                ];
                break;
        }
        return [
            'name' => $name,
            'data' => $data,
        ];
    }

    private function dateMapper($index)
    {
        $start_base = Carbon::today();
        $end_base = Carbon::today();
        switch ($index) {
            case self::TODAY:
                $start = $start_base->startOfDay();
                $end = $end_base->endOfDay();
                $name = 'Today';
                break;
            case self::YESTERDAY:
                $start = $start_base->subDay()->startOfDay();
                $end = $end_base->subDay()->endOfDay();
                $name = 'Yesterday';
                break;
            case self::THIS_WEEK:
                $start = $start_base->startofWeek();
                $end = $end_base->endofWeek();
                $name = 'This Week';
                break;
            case self::LAST_WEEK:
                $start = $start_base->subWeek()->startOfWeek();
                $end = $end_base->subWeek()->endOfWeek();
                $name = 'Last Week';
                break;
            case self::THIS_MONTH:
                $start = $start_base->startOfMonth();
                $end = $end_base->endOfMonth();
                $name = 'This Month';
                break;
            case self::LAST_MONTH:
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

    private function makeModel($data, $date, $type, $account)
    {
        $call = Recording::make([
            'records'   => $data['records'],
            'type'      => $type['name'],
            'date_name' => $date['name'],
            'date_from' => $date['start'],
            'date_to'   => $date['end'],
        ]);
        $call->client()->associate(
            Client::firstWhere('freshsales_id', $account['id'])
        );
        $call->save();
    }
}
