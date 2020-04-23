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
}
