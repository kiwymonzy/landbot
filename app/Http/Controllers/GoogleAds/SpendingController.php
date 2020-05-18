<?php

namespace App\Http\Controllers\GoogleAds;

use App\Models\Client;
use App\Models\Spending;
use Illuminate\Http\Request;

class SpendingController extends BaseController
{
    /**
     * Get current spending
     *
     * @param Request $request
     * @return void
     */
    public function spending(Request $request)
    {
        $account = $this->fetchAccount($request->phone)['sales_account'];

        if (!$this->accountIsValid($account))
            abort(403, 'This feature is not enabled on your account');

        $ids = $this->parseAdWordsIds($account);

        $spending = $this->fetchSpending($ids, $request->date);

        $res = [
            'name' => $account['name'],
            'spending' => priceFormat($spending->sum()),
        ];

        $spend = Spending::make([
            'amount' => $spending->sum(),
            'date_name' => $this->dateMapper($request->date)['name']
        ]);
        $spend->client()->associate(
            Client::firstWhere('freshsales_id', $account['id'])
        );
        $spend->save();

        return $this->sendResponse('Success!', $res);
    }

    /**
     * Fetch spending of from AdWord IDs
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

        $spending = collect([]);

        foreach ($accountIds as $id) {
            $sum = 0;
            $stream = $serviceClient->search($id, $query);
            foreach ($stream->iterateAllElements() as $row) {
                $sum += $row->getMetrics()->getCostMicrosUnwrapped();
            }
            $spending->push($sum);
        }

        return $spending->map(function ($item) {
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
        return isset($account['custom_field']['cf_adwords_ids']);
    }
}
