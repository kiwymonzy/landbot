<?php

namespace App\Library\GoogleAds;

use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\Lib\V3\GoogleAdsClientBuilder;

class GoogleAds
{
    private $client;
    private $oauth;

    public function __construct()
    {
        $this->oauth = (new OAuth2TokenBuilder)
            ->fromFile(config_path('google-ads.ini'))
            ->build();

        $this->client = (new GoogleAdsClientBuilder)
            ->fromFile(config_path('google-ads.ini'))
            ->withOAuth2Credential($this->oauth)
            ->build();

    }

    /**
     * Get GoogleAds client
     *
     * @return \Google\Ads\GoogleAds\Lib\V3\GoogleAdsClient
     */
    public function client()
    {
        return $this->client;
    }
}
