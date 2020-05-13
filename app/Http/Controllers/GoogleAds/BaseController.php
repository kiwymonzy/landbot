<?php

namespace App\Http\Controllers\GoogleAds;

use App\Http\Controllers\Controller;
use App\Library\GoogleAds\GoogleAds;
use Illuminate\Support\Str;

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
     * @return \Illuminate\Support\Collection
     */
    public function parseAdWordsIds($account)
    {
        $string = $account['custom_field']['cf_adwords_ids'];
        return Str::of($string)->replace('-', '')->explode("\n");
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
