<?php

namespace App\Http\Controllers\GoogleAds;

use Carbon\Carbon;
use Illuminate\Http\Request;

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
     * Gets a list of campaigns with the corresponding budgets
     *
     * Budgets that have a value of 1 are considered paused and thus are not displayed
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function activeCampaigns(Request $request)
    {
        $account = $this->fetchAccount($request->phone)['sales_account'];

        if (!$this->accountIsValid($account))
            abort(403, 'This feature is not enabled on your account');

        $id = $this->parseAdWordsIds($account)[0];

        $campaigns = $this->fetchCampaigns($id)->filter(function ($i) {
            return $i['budget'] > 1;
        });

        $res = [
            'name' => $account['name'],
            'current_budget' => priceFormat($campaigns->sum('budget')),
            'campaigns' => $this->formatCampaigns($campaigns),
        ];

        return $this->sendResponse('', $res);
    }

    /**
     * Check if account has feature enabled
     *
     * @param array $account
     * @return bool
     */
    public function accountIsValid($account)
    {
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

    /**
     * Format campaign list
     *
     * Groups campaign list by budget id
     * Forms a string to display in LandBot
     *
     * @param \Illuminate\Support\Collection $campaigns
     * @return \Illuminate\Support\Collection
     */
    public function formatCampaigns($campaigns)
    {
        $res = collect();

        foreach ($campaigns->groupBy('budget_id') as $id => $items) {
            $val = [
                'budget_id' => $id,
                'budget' => $items[0]['budget'],
                'items' => $items
            ];

            if ($items->count() > 1) {
                $val['string'] = $items->pluck('name')->map(function ($i) {
                    return '(' . $i . ')';
                })->join('') . ' $' . $items[0]['budget'];
            } else {
                $val['string'] = $items[0]['name'] . ' $' . $items[0]['budget'];
            }

            $res->push($val);
        }

        return $res;
    }
}
