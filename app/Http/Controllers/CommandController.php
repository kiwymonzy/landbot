<?php

namespace App\Http\Controllers;

use App\Library\FreshSales\FreshSales;
use App\Library\GoogleAds\GoogleAds;
use Google\Ads\GoogleAds\Util\FieldMasks;
use Google\Ads\GoogleAds\Util\V3\ResourceNames;
use Google\Ads\GoogleAds\V3\Enums\CampaignStatusEnum\CampaignStatus;
use Google\Ads\GoogleAds\V3\Resources\Campaign;
use Google\Ads\GoogleAds\V3\Services\CampaignOperation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CommandController extends Controller
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
        $account = $this->fetchAccount($request->phone);
        // $account = $this->fetchAccount('6285881233829');

        $ids = $this->parseAdWordsIds($account);

        $spending = $this->fetchSpending($ids, $request->date);

        $res = [
            'name' => $account['name'],
            'spending' => '$' . number_format($spending->sum(), 2),
        ];

        return $this->sendResponse('Success!', $res);
    }

    /**
     * Enable all ads associated
     *
     * @param Request $request
     * @return void
     */
    public function enable(Request $request)
    {
        $account = $this->fetchAccount($request->phone);
        // $account = $this->fetchAccount('6285881233829');

        $ids = $this->parseAdWordsIds($account);

        $this->updateAds($ids, 1);

        $res = [
            'name' => $account['name']
        ];

        return $this->sendResponse('Success!', $res);
    }

    /**
     * Pause all ads associated
     *
     * @param Request $request
     * @return void
     */
    public function pause(Request $request)
    {
        $account = $this->fetchAccount($request->phone);
        // $account = $this->fetchAccount('6285881233829');

        $ids = $this->parseAdWordsIds($account);

        $this->updateAds($ids, 2);

        $res = [
            'name' => $account['name']
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
     * @param Integer $date
     * @return \Illuminate\Support\Collection
     */
    public function fetchSpending(Array $ids, $dateIndex)
    {
        $serviceClient = $this->adsClient->getGoogleAdsServiceClient();

        $date = $this->dateMapper($dateIndex);
        $query = 'SELECT metrics.cost_micros FROM customer WHERE segments.date DURING ' . $date;

        $spending = collect([]);

        foreach ($ids as $id) {
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

    public function updateAds(Array $accountIds, $status = 1)
    {
        $serviceClient = $this->adsClient->getGoogleAdsServiceClient();
        $query = "SELECT campaign.id FROM campaign";

        foreach ($accountIds as $id) {
            $stream = $serviceClient->search($id, $query);
            $operations = collect([]);
            foreach ($stream->iterateAllElements() as $row) {
                $cID = $row->getCampaign()->getIdUnwrapped();

                $c = new Campaign();
                $c->setResourceName(ResourceNames::forCampaign($id, $cID));
                $c->setStatus($this->campaignStatusMapper($status));

                $cOp = new CampaignOperation();
                $cOp->setUpdate($c);
                $cOp->setUpdateMask(FieldMasks::allSetFieldsOf($c));

                $operations->push($cOp);
            }
            // dd('stop before making changes');
            $campaignService = $this->adsClient->getCampaignServiceClient();
            $res = $campaignService->mutateCampaigns($id, $operations->toArray());
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

    public function campaignStatusMapper($index)
    {
        switch ($index) {
            case 1:
                return CampaignStatus::ENABLED;
            case 2:
                return CampaignStatus::PAUSED;
            default:
                return CampaignStatus::PAUSED;
        }
    }

    /**
     * Parse AdWords IDs from FreshSales accounts
     *
     * @param \Illuminate\Support\Collection $account
     * @return array
     */
    public function parseAdWordsIds($account)
    {
        return explode("\n", str_replace('-', '', $account['custom_field']['cf_adwords_ids']));
    }
}
