<?php

namespace App\Http\Controllers\GoogleAds;

use Carbon\Carbon;

class MutationController extends BaseController
{
    /**
     * Fetch campaigns from Google Ads account
     *
     * @param string $id
     * @return \Illuminate\Support\Collection
     */
    public function fetchCampaigns($id)
    {
        $serviceClient = $this->adsClient()->getGoogleAdsServiceClient();

        $query = 'SELECT campaign.id, campaign.name, campaign_budget.amount_micros, campaign_budget.id, campaign.advertising_channel_type FROM campaign';

        $campaigns = collect();
        $stream = $serviceClient->search($id, $query);
        foreach ($stream->iterateAllElements() as $row) {
            if (!$this->passFilter($row)) continue;
            $id = $row->getCampaign()->getIdUnwrapped();
            $name = $row->getCampaign()->getName()->getValue();
            $budget = $row->getCampaignBudget()->getAmountMicrosUnwrapped();
            $budget_id = $row->getCampaignBudget()->getIdUnwrapped();

            $campaigns->push([
                'id' => $id,
                'name' => $name,
                'budget' => $budget / 1000000,
                'budget_id' => $budget_id,
            ]);
        }

        return $campaigns;
    }

    /**
     * Check if account has feature enabled
     *
     * @param array $account
     * @return bool
     */
    public function accountIsValid($account)
    {
        return true;
        return filter_var($account['custom_field']['cf_budget_recommendation'], FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Returns a DateTime when the change will end
     *
     * Options:
     * 1. Today
     * 2. Today and Tomorrow
     * 2. Next 3 Days
     * 2. Next Week
     *
     * @param int $index
     * @return \Carbon\Carbon
     */
    public function durationMapper($index)
    {
        $date = Carbon::today();
        switch ($index) {
            case 1:
                $date->addDays(1);
                break;
            case 2:
                $date->addDays(2);
                break;
            case 3:
                $date->addDays(3);
                break;
            case 4:
                $date->addWeek()->startOfWeek();
                break;
            default:
                $date->addDay();
                break;
        }

        return $date->setTime(9, 0);
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
