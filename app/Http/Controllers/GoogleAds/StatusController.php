<?php

namespace App\Http\Controllers\GoogleAds;

use App\Jobs\MutateCampaignBudget;
use Google\Ads\GoogleAds\Util\FieldMasks;
use Google\Ads\GoogleAds\Util\V3\ResourceNames;
use Google\Ads\GoogleAds\V3\Enums\CampaignStatusEnum\CampaignStatus;
use Google\Ads\GoogleAds\V3\Resources\Campaign;
use Google\Ads\GoogleAds\V3\Services\CampaignOperation;
use Illuminate\Http\Request;

class StatusController extends MutationController
{
    /**
     * Enable all ads associated
     *
     * @param Request $request
     * @return void
     */
    public function enable(Request $request)
    {
        $account = $this->fetchAccount($request->phone)['sales_account'];

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
        $account = $this->fetchAccount($request->phone)['sales_account'];

        $id = $this->parseAdWordsIds($account)[0];

        $campaigns = $this->fetchCampaigns($id)->filter(function ($i) {
            return $i['budget'] > 1;
        });

        $campaign = $this->formatCampaigns($campaigns)[$request->campaign - 1];

        $amountOld = $campaign['budget'];
        $amountNew = 1;
        $delay = $this->durationMapper($request->duration);

        MutateCampaignBudget::dispatch($id, $campaign['budget_id'], $amountNew);
        MutateCampaignBudget::dispatch($id, $campaign['budget_id'], $amountOld)
            ->delay($delay);

        return $this->sendResponse('', [
            'old_budget' => $amountOld,
            'new_budget' => $amountNew,
            'reverted' => $delay->format("l M d, Y h:ia"),
        ]);
    }

    /**
     * Update status of all campaigns in accounts
     *
     * TODO: Find way to mutate video ads
     * TODO: Find way to retrieve smart campaigns
     * @param Array|Collection $accountIds
     * @param integer $status
     * @return void
     */
    public function updateAds($accountIds, $status = 1)
    {
        $serviceClient = $this->adsClient()->getGoogleAdsServiceClient();
        $query = "SELECT campaign.id, campaign.advertising_channel_type FROM campaign";

        $campaignService = $this->adsClient()->getCampaignServiceClient();

        foreach ($accountIds as $id) {
            $stream = $serviceClient->search($id, $query);
            $operations = collect([]);
            foreach ($stream->iterateAllElements() as $row) {
                if (!$this->passFilter($row)) continue;

                $cID = $row->getCampaign()->getIdUnwrapped();

                $c = new Campaign();
                $c->setResourceName(ResourceNames::forCampaign($id, $cID));
                $c->setStatus($this->campaignStatusMapper($status));

                $cOp = new CampaignOperation();
                $cOp->setUpdate($c);
                $cOp->setUpdateMask(FieldMasks::allSetFieldsOf($c));

                $operations->push($cOp);
            }
            $campaignService->mutateCampaigns($id, $operations->toArray());
        }
        $campaignService->close();
    }

    /**
     * Map status by index
     *
     * @param Integer $index
     * @return String
     */
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
}
