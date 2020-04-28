<?php

namespace App\Http\Controllers\GoogleAds;

use App\Http\Controllers\Controller;
use App\Library\GoogleAds\GoogleAds;
use Illuminate\Http\Request;

class BaseController extends Controller
{
    private $adsClient;

    public function __construct()
    {
        $this->adsClient = (new GoogleAds())->client();
    }

    public function adsClient()
    {
        return $this->adsClient;
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

    /**
     * Filter campaigns based on blacklist
     *
     * @param mixed $row
     * @return boolean
     */
    public function passFilter($row)
    {
        /**
         * Campaign Type Enum
         *
         * 0: UNSPECIFIED
         * 1: UNKNOWN
         * 2: SEARCH
         * 3: DISPLAY
         * 4: SHOPPING
         * 5: HOTEL
         * 6: VIDEO
         */
        $blackListCampaignTypes = collect([6]);
        $campaignType = $row->getCampaign()->getAdvertisingChannelType();
        if ($blackListCampaignTypes->contains($campaignType)) {
            return false;
        }
        return true;
    }
}
