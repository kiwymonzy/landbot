<?php

namespace App\Http\Controllers\GoogleAds;

use App\Models\Client;
use App\Models\Spending;
use Illuminate\Http\Request;

class SpendingController extends BaseController
{
    /**
     * Get current spendings
     *
     * @param Request $request
     * @return void
     */
    public function spendings(Request $request)
    {
        $account = $this->fetchAccount($request->phone);

        if (!$this->accountIsValid($account))
            abort(403, 'This feature is not enabled on your account');

        $ids = $this->parseAdWordsIds($account);

        $spendings = $this->fetchSpending($ids, $request->date);

        $this->makeModel($account, $spendings, $request->date);

        return $this->sendResponse(
            'Success!',
            [
                'name' => $account['name'],
                'spendings' => priceFormat($spendings->sum()),
            ]
        );
    }

    /**
     * Fetch spendings  from AdWord IDs
     *
     * @param Array|Collection $accountIds
     * @param Integer $date
     * @return \Illuminate\Support\Collection
     */
    public function fetchSpending($accountIds, $dateIndex)
    {
        $serviceClient = $this->adsClient()->getGoogleAdsServiceClient();

        $date = $this->dateMapper($dateIndex)['google'];
        $query = 'SELECT metrics.cost_micros FROM customer WHERE segments.date DURING ' . $date;

        $spendings = collect([]);

        foreach ($accountIds as $id) {
            $sum = 0;
            $stream = $serviceClient->search($id, $query);
            foreach ($stream->iterateAllElements() as $row) {
                $sum += $row->getMetrics()->getCostMicrosUnwrapped();
            }
            $spendings->push($sum);
        }

        return $spendings->map(function ($item) {
            return $item / 1000000;
        });
    }

    /**
     * Check if account has feature enabled
     *
     * @param array $account
     * @return bool
     */
    public function accountIsValid($account)
    {
        return !is_null($account['custom_field']['cf_adwords_ids']);
    }

    public function makeModel($account, $spendings, $dateIndex)
    {
        $spending = Spending::make([
            'amount' => $spendings->sum(),
            'date_name' => $this->dateMapper($dateIndex)['name']
        ]);

        $client = Client::firstWhere('freshsales_id', $account['id']);
        $spending->client()->associate($client);

        $spending->save();
    }
}
