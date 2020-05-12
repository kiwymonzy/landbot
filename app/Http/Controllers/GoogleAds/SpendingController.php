<?php

namespace App\Http\Controllers\GoogleAds;

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

        $ids = $this->parseAdWordsIds($account);

        $spending = $this->fetchSpending($ids, $request->date);

        $res = [
            'name' => $account['name'],
            'spending' => priceFormat($spending->sum()),
        ];

        return $this->sendResponse('Success!', $res);
    }

    /**
     * Fetch spending of from AdWord IDs
     *
     * @param Array $accountIds
     * @param Integer $date
     * @return \Illuminate\Support\Collection
     */
    public function fetchSpending(array $accountIds, $dateIndex)
    {
        $serviceClient = $this->adsClient()->getGoogleAdsServiceClient();

        $date = $this->dateMapper($dateIndex);
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
}
