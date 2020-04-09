<?php

namespace App\Http\Controllers;

use App\Library\FreshSales\FreshSales;
use App\Library\GoogleAds\GoogleAds;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SpendingController extends Controller
{
    private $adsClient;

    public function __construct()
    {
        $this->adsClient = (new GoogleAds())->client();
    }

    /**
     * Get current spending
     *
     * @param Request $request
     * @return void
     */
    public function spending(Request $request)
    {
        // $account = $this->fetchAccount($request->phone);
        $account = $this->fetchAccount('6285881233829');

        $ids = explode("\n", str_replace('-', '', $account['custom_field']['cf_adwords_ids']));
        $date = $this->dateMapper($request->date);

        $spending = $this->fetchSpending($ids, $date)->map(function($item) { return $item / 1000000; });

        $res = [
            'name' => $account['name'],
            'spending' => '$' . number_format($spending->sum(), 2),
        ];

        return $this->sendResponse('Success!', $res);
    }

    /**
     * Fetch account from FreshSales API
     *
     * @param String $channel
     * @return \Illuminate\Support\Collection
     */
    public function fetchAccount(String $number)
    {
        // Create FreshSales client
        $fs = new FreshSales();

        // Search for number name in accounts
        $accounts = $fs->account()->search($number);

        // Find first exact match in number name custom field
        foreach ($accounts as $acc) {
            $match = $acc['more_match'];
            if ($match['field_name'] == 'WA Number' && $match['field_value'] == $number) {
                return $fs->account()->get($acc['id']);
            }
        }

        // Throw error when none found
        abort(404, 'Phone number not found');
    }

    /**
     * Fetch spending of from AdWord IDs
     *
     * @param Array $ids
     * @param String $date
     * @return \Illuminate\Support\Collection
     */
    public function fetchSpending(Array $ids, String $date)
    {
        $serviceClient = $this->adsClient->getGoogleAdsServiceClient();
        $query = 'SELECT campaign.name, metrics.cost_micros FROM campaign WHERE segments.date DURING ' . $date;

        $spending = collect([]);
        foreach ($ids as $id) {
            $sum = 0;
            $stream = $serviceClient->search($id, $query, ['pageSize' => 10000]);
            foreach ($stream->iterateAllElements() as $row) {
                $sum += $row->getMetrics()->getCostMicrosUnwrapped();
            }
            $spending->push($sum);
        }
        return $spending;
    }

    public function fetchAds(Array $accountIds, $i)
    {
        $serviceClient = $this->adsClient->getGoogleAdsServiceClient();
        $query = "SELECT ad_group.id, ad_group.name, ad_group_ad.status, ad_group_ad.ad.id, ad_group_ad.ad.name FROM ad_group_ad";

        foreach ($accountIds as $id) {
            $stream = $serviceClient->searchStream($id, $query);
            foreach ($stream->readAll() as $response) {
                foreach ($response->getResults() as $row) {
                    $agID = $row->getAdGroup()->getIdUnwrapped();
                    $agNa = $row->getAdGroup()->getNameUnwrapped();
                    $agaaID = $row->getAdGroupAd()->getAd()->getIdUnwrapped();
                    $agaaNa = $row->getAdGroupAd()->getAd();
                    dd($agaaNa);
                    printf("%d\t%s\t%d\t%s<br>", $agID, $agNa, $agaaID, $agaaNa);
                }
                dd('hi');
            }
        }
    }

    /**
     * Map date index
     *
     * @param Integer $index
     * @return String
     */
    public function dateMapper($index)
    {
        switch ($index) {
            case 1:
                return 'TODAY';
            case 2:
                return 'YESTERDAY';
            case 3:
                return 'THIS_WEEK_SUN_TODAY';
            case 4:
                return 'LAST_WEEK_SUN_SAT';
            case 5:
                return 'THIS_MONTH';
            case 6:
                return 'LAST_MONTH';
            default:
                return 'TODAY';
        }
    }
}
