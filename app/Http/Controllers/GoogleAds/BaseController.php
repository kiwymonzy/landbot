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
                $google = 'TODAY';
                $name = 'Today';
                break;
            case 2:
                $google = 'YESTERDAY';
                $name = 'Yesterday';
                break;
            case 3:
                $google = 'THIS_WEEK_SUN_TODAY';
                $name = 'This Week';
                break;
            case 4:
                $google = 'LAST_WEEK_SUN_SAT';
                $name = 'Last Week';
                break;
            case 5:
                $google = 'THIS_MONTH';
                $name = 'This Month';
                break;
            case 6:
                $google = 'LAST_MONTH';
                $name = 'Last Month';
                break;
            default:
                $google = 'TODAY';
                $name = 'Today';
                break;
        }
        return [
            'google' => $google,
            'name' => $name,
        ];
    }
}
