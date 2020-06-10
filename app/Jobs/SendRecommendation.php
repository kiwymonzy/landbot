<?php

namespace App\Jobs;

use App\Library\LandBot\LandBot;
use App\Models\BudgetRecommendation;
use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendRecommendation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $clientId;
    protected $adsAccountId;
    protected $adsCampaignBudgetId;
    protected $adsCampaignBudget;
    protected $adsCampaigns;
    protected $adsCampaignsString;
    protected $calls;
    protected $change;

    private const BOT_ID = '505201';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        $calls,
        $change,
        $adsCampaignBudget,
        $adsCampaigns,
        $adsAccountId,
        $adsCampaignBudgetId,
        $clientId
    ){
        $this->calls               = $calls;
        $this->change              = $change;
        $this->adsCampaignBudget   = $adsCampaignBudget;
        $this->adsCampaigns        = $adsCampaigns;
        $this->adsAccountId        = $adsAccountId;
        $this->adsCampaignBudgetId = $adsCampaignBudgetId;
        $this->clientId            = $clientId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->adsCampaignsString = $this->formatCampaignString($this->adsCampaigns);
        $this->makeModel();
        $this->sendRecommendation();
    }

    private function sendRecommendation()
    {
        $client = Client::find($this->clientId);
        $landbotCustomer = (new LandBot())->customer();

        $landbotCustomer->assignBot($client->landbot_id, self::BOT_ID);
    }

    private function makeModel()
    {
        $rec = BudgetRecommendation::make([
            'campaign'   => $this->adsCampaignsString,
            'budget'     => $this->adsCampaignBudget,
            'calls'      => $this->calls,
            'change'     => priceFormat($this->change),
            'account_id' => $this->adsAccountId,
            'budget_id'  => $this->adsCampaignBudgetId,
        ]);
        $rec->client()->associate($this->clientId);
        $rec->save();
    }

    private function formatCampaignString($campaigns)
    {
        if ($campaigns->count() > 1) {
            $string = '(';
            $string .= $campaigns->join(')(');
            $string .= ')';
            return $string;
        }
        return $campaigns->join('');
    }
}
