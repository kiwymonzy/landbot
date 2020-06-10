<?php

namespace App\Jobs;

use App\Library\GoogleAds\GoogleAds;
use Google\Ads\GoogleAds\Util\FieldMasks;
use Google\Ads\GoogleAds\Util\V3\ResourceNames;
use Google\Ads\GoogleAds\V3\Resources\CampaignBudget;
use Google\Ads\GoogleAds\V3\Services\CampaignBudgetOperation;
use Google\ApiCore\ApiException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MutateCampaignBudget implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $adsAccountId;
    protected $adsCampaignBudgetId;
    protected $amount;

    /**
     * Create a new job instance.
     *
     * @param string $adsAccountId
     * @param int $adsCampaignId
     * @param int $amount
     * @return void
     */
    public function __construct(
        $adsAccountId,
        $adsCampaignBudgetId,
        $amount
    ){
        $this->adsAccountId = $adsAccountId;
        $this->adsCampaignBudgetId = $adsCampaignBudgetId;
        $this->amount = $amount;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $client = (new GoogleAds)->client();

        $campaignBudget = new CampaignBudget([
            'resource_name' => ResourceNames::forCampaignBudget($this->adsAccountId, $this->adsCampaignBudgetId),
            'amount_micros' => $this->amount * 1000000
        ]);

        $campaignBudgetOperation = new CampaignBudgetOperation();
        $campaignBudgetOperation->setUpdate($campaignBudget);
        $campaignBudgetOperation->setUpdateMask(FieldMasks::allSetFieldsOf($campaignBudget));

        $campaignBudgetService = $client->getCampaignBudgetServiceClient();

        try {
            $campaignBudgetService->mutateCampaignBudgets($this->adsAccountId, [$campaignBudgetOperation]);
        } catch (ApiException $e) {
            Log::error($e);
        } finally {
            $campaignBudgetService->close();
        }
    }
}
