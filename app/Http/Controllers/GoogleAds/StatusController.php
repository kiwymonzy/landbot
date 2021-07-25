<?php

namespace App\Http\Controllers\GoogleAds;

use App\Jobs\MutateCampaignBudget;
use App\Models\Client;
use App\Models\StatusMutation;
use Carbon\Carbon;
use Google\Ads\GoogleAds\Util\FieldMasks;
use Google\Ads\GoogleAds\Util\V3\ResourceNames;
use Google\Ads\GoogleAds\V3\Enums\CampaignStatusEnum\CampaignStatus;
use Google\Ads\GoogleAds\V3\Resources\Campaign;
use Google\Ads\GoogleAds\V3\Services\CampaignOperation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StatusController extends MutationController
{
    /**
     * Pause all ads associated
     *
     * @param Request $request
     * @return void
     */
    public function pause(Request $request)
    {
        $account = $this->fetchAccount($request->phone)['sales_account'];

        $campaigns = $this->fetchActiveCampaigns($account);
        $campaigns = $this->formatCampaigns($campaigns);

        $budget_new = 1;
        $delay = $this->durationMapper($request->duration);

        foreach ($campaigns as $campaign) {
            $this->mutateCampaign($campaign, $budget_new, $delay['date']);
            $this->storeMutation($account['id'], $campaign, $delay, true);
        }

        return $this->sendResponse('', [
            'reverted' => $delay['date']->format("l M d, Y h:ia"),
        ]);
    }

    /**
     * Returns a DateTime when the change will end
     *
     * Options:
     * 1. Today
     * 2. Today and Tomorrow
     * 3. Next 3 Days
     * 4. Next 7 Days
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
                $name = 'Today';
                break;
            case 2:
                $date->addDays(2);
                $name = 'Today and Tomorrow';
                break;
            case 3:
                $date->addDays(3);
                $name = 'Next 3 Days';
                break;
            case 4:
                $date->addDays(7);
                $name = 'Next 7 Days';
                break;
            default:
                $date->addDay();
                $name = 'Today';
                break;
        }

        return [
            'name' => $name,
            'date' => $date->setTime(9, 0)
        ];
    }

    private function storeMutation($account, $campaign, $duration, $pause = true)
    {
        $status = StatusMutation::make([
            'status_old'  => $pause ? 'Active' : 'Paused',
            'status_new'  => $pause ? 'Paused' : 'Active',
            'campaign'    => explode(' $', $campaign['string'])[0],
            'date_name'   => $duration['name'],
            'date_revert' => $duration['date'],
        ]);
        $status->client()->associate(
            Client::firstWhere('freshsales_id', $account)
        );
        $status->save();
    }
}
