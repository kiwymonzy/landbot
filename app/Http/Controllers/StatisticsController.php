<?php

namespace App\Http\Controllers;

use App\Library\GoogleAds\GoogleAds;
use App\Library\WildJar\WildJar;
use App\Models\Client;
use App\Models\Statistic;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StatisticsController extends Controller
{
    public function __construct()
    {
        $this->wildJar = new WildJar;
        $this->adsClient = (new GoogleAds())->client();
    }

    public function statistics(Request $request)
    {
        $fsAccount = $this->fetchAccount($request->phone)['sales_account'];

        if (!$this->accountIsValid($fsAccount))
            abort(403, 'This feature is not enabled on your account');

        $dates = $this->dateMapper($request->date);

        $calls = $this->getCalls($fsAccount, $dates);
        $stats = $this->getStats($fsAccount, $dates);

        $result = $stats + $calls;
        $result['calls'] = $this->calls($result);
        $result['cost_per_call'] = $this->costPerCall($result);
        $result['click_to_call'] = $this->clickToCall($result);

        $this->makeModel($result, $dates, $fsAccount);

        return $this->sendResponse('Retrieved statistics', $result);
    }

    private function calls($data)
    {
        return $data['answered'] + $data['missed'];
    }

    private function costPerCall($data)
    {
        ['calls' => $calls] = $data;
        if ($calls == 0) $calls = 1;
        return priceFormat($data['spendings'] / $calls);
    }

    private function clickToCall($data)
    {
        [
            'clicks' => $clicks,
            'calls' => $calls,
        ] = $data;
        if ($clicks == 0) $clicks = 1;
        return priceFormat($calls / $clicks * 100);
    }

    private function getCalls($fsAccount, $dates)
    {
        $wjAccount = $this->parseWildJarId($fsAccount);

        $wjAccounts = $this->fetchWJSubAccounts($wjAccount);

        $calls = $this->fetchCalls($wjAccounts, $dates);

        $data = [
            'answered' => intval($calls['answeredTot']),
            'missed' => $calls['missedTot'] + $calls['abandonedTot'],
        ];

        return $data;
    }

    private function fetchCalls($accounts, $dates)
    {
        $data = [
            'account' => $accounts->join(','),
            'datefrom' => $dates['start'],
            'dateto' => $dates['end'],
            'timezone' => 'Australia/Sydney',
        ];

        return $this->wildJar->summary()->filter($data)['summary'];
    }

    private function getStats($fsAccount, $dates)
    {
        $ids = $this->parseAdWordsIds($fsAccount);

        $data = $this->fetchStats($ids, $dates);

        return [
            'name' => $fsAccount['name'],
            'spendings' => priceFormat($data['spendings']->sum()),
            'clicks' => $data['clicks']->sum(),
        ];
    }

    /**
     * Fetch spendings  from AdWord IDs
     *
     * @param Array|Collection $accountIds
     * @param Integer $date
     * @return \Illuminate\Support\Collection
     */
    private function fetchStats($accountIds, $dates)
    {
        $serviceClient = $this->adsClient->getGoogleAdsServiceClient();

        $date = $dates['google'];
        $query = 'SELECT metrics.cost_micros, metrics.clicks FROM customer WHERE segments.date DURING ' . $date;

        $result = collect([
            'spendings' => collect(),
            'clicks' => collect(),
        ]);

        foreach ($accountIds as $id) {
            $spend = 0;
            $click = 0;
            $stream = $serviceClient->search($id, $query);
            foreach ($stream->iterateAllElements() as $row) {
                $metrics = $row->getMetrics();
                $spend += $metrics->getCostMicrosUnwrapped();
                $click += $metrics->getClicksUnwrapped();
            }
            $result['spendings']->push($spend / 1000000);
            $result['clicks']->push($click);
        }

        return $result;
    }

    /**
     * Map dates for WildJar
     *
     * @param integer $index
     * @return array
     */
    private function dateMapper($index)
    {
        $start_base = Carbon::today();
        $end_base = Carbon::today();
        switch ($index) {
            case 1:
                $start = $start_base->startOfDay();
                $end = $end_base->endOfDay();
                $google = 'TODAY';
                $name = 'Today';
                break;
            case 2:
                $start = $start_base->subDay()->startOfDay();
                $end = $end_base->subDay()->endOfDay();
                $google = 'YESTERDAY';
                $name = 'Yesterday';
                break;
            case 3:
                $start = $start_base->startofWeek();
                $end = $end_base->endofWeek();
                $google = 'THIS_WEEK_SUN_TODAY';
                $name = 'This Week';
                break;
            case 4:
                $start = $start_base->subWeek()->startOfWeek();
                $end = $end_base->subWeek()->endOfWeek();
                $google = 'LAST_WEEK_SUN_SAT';
                $name = 'Last Week';
                break;
            case 5:
                $start = $start_base->startOfMonth();
                $end = $end_base->endOfMonth();
                $google = 'THIS_MONTH';
                $name = 'This Month';
                break;
            case 6:
                $start = $start_base->subMonth()->startOfMonth();
                $end = $end_base->subMonth()->endOfMonth();
                $google = 'LAST_MONTH';
                $name = 'Last Month';
                break;
            default:
                $start = $start_base->startOfDay();
                $end = $end_base->endOfDay();
                $google = 'TODAY';
                $name = 'Today';
                break;
        }
        return [
            'start' => $start->format('Y-m-d\TH:i:s'),
            'end' => $end->format('Y-m-d\TH:i:s'),
            'google' => $google,
            'name' => $name,
        ];
    }

    /**
     * Parse WildJar sub accounts from ID
     *
     * @param \Illuminate\Support\Collection $account
     * @return \Illuminate\Support\Collection
     */
    private function fetchWJSubAccounts($account)
    {
        $allAccounts = $this->wildJar->account()->all();

        $allAccountIds = $allAccounts->filter(function ($q) use ($account) {
            return $q['father'] == $account;
        })->pluck('id');

        return $allAccountIds->push($account);
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
     * Parse AdWords IDs from FreshSales accounts
     *
     * @param \Illuminate\Support\Collection $account
     * @return \Illuminate\Support\Collection
     */
    public function parseAdWordsIds($account)
    {
        $string = $account['custom_field']['cf_adwords_ids'];
        return Str::of($string)->replace('-', '')->explode("\n");
    }

    /**
     * Check if account has feature enabled
     *
     * @param array $account
     * @return bool
     */
    private function accountIsValid($account)
    {
        return isset($account['custom_field']['cf_wildjar_id']) &&
            !is_null($account['custom_field']['cf_adwords_ids']);
    }

    private function makeModel($data, $dates, $account)
    {
        $statistic = Statistic::make([
            'spendings'     => $data['spendings'],
            'cost_per_call' => $data['cost_per_call'],
            'click_to_call' => $data['click_to_call'],
            'clicks'        => $data['clicks'],
            'answered'      => $data['answered'],
            'missed'        => $data['missed'],
            'date_name'     => $dates['name'],
            'date_from'     => $dates['start'],
            'date_to'       => $dates['end'],
        ]);

        $client = Client::firstWhere('freshsales_id', $account['id']);
        $statistic->client()->associate($client);

        $statistic->save();
    }
}
