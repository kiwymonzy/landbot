<?php

namespace App\Console\Commands;

use App\Jobs\SendRecommendation;
use App\Library\FreshSales\FreshSales;
use App\Library\GoogleAds\GoogleAds;
use App\Library\LandBot\LandBot;
use App\Library\WildJar\WildJar;
use App\Models\Client;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class OutgoingRecommendation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recommendations:queue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queue outgoing recommendations to be sent';

    private $freshSalesClient;
    private $landBotClient;
    private $googleAdsClient;
    private $wildJarClient;
    private $wildJarAccounts;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->freshSalesClient = new FreshSales;
        $this->landBotClient = new LandBot;
        $this->wildJarClient = new WildJar;
        $this->googleAdsClient = (new GoogleAds)->client()->getGoogleAdsServiceClient();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $clients = Client::get(['id', 'phone', 'company', 'freshsales_id']);

        foreach ($clients as $client) {
            $freshSalesAccount = $this->handleFreshSales($client);
            $this->handleLandBot($client);

            if (!$freshSalesAccount['allowed']) continue;

            [
                'campaigns' => $campaignCount,
                'cpcs' => $clientCPCs,
            ] = $this->handleCPCs($freshSalesAccount);

            $today = collect($clientCPCs->pop());
            $weekMedian = $clientCPCs->median('data.*.cpc');

            for ($i = 0; $i < $campaignCount; $i++) {
                $median = $weekMedian[$i];
                [
                    'cpc' => $cpc,
                    'calls' => $calls,
                    'budget' => $budget,
                ] = $today['data'][$i];

                if($median > 3 && $budget > 1) {
                    $change = $this->relativeChange($median, $cpc);
                    if ($change >= 10) {
                        [
                            'budget' => $adsCampaignBudget,
                            'campaigns' => $adsCampaigns,
                            'account_id' => $adsAccountId,
                            'budget_id' => $adsCampaignBudgetId
                        ] = $today['data'][$i];
                        dispatch(new SendRecommendation(
                            $calls,
                            $change,
                            $adsCampaignBudget,
                            $adsCampaigns,
                            $adsAccountId,
                            $adsCampaignBudgetId,
                            $client->id
                        ));
                        break;
                    }
                }
            }
        }
    }

    /**
     * Fetch account information from FreshSales
     *
     * @param \Illuminate\Support\Collection $clients
     * @return \Illuminate\Support\Collection
     */
    private function handleFreshSales($client)
    {
        $freshSalesAccount = $this->freshSalesClient
            ->account()
            ->get($client['freshsales_id'])['sales_account'];

        return [
            'allowed'     => $this->actionIsAllowed($freshSalesAccount),
            'adwords_ids' => $this->parseAdWordsIds($freshSalesAccount),
            'wildjar_id'  => $this->parseWildJarId($freshSalesAccount),
        ];
    }

    private function handleLandBot($client)
    {
        if (is_null($client->landbot_id)) {
            $phone = substr($client->phone, 1);
            $landbot_id = $this->landBotClient
                ->customer()
                ->searchBy('phone', $phone)
                ['customers'][0]['id'];
            $client->update([
                'landbot_id' => $landbot_id
            ]);
        }
    }

    /**
     * Ensure if the action is allowed on the FreshSales account
     *
     * @param array $account
     * @return bool
     */
    private function actionIsAllowed($account)
    {
        return filter_var($account['custom_field']['cf_budget_recommendation'], FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Handle fethcing data from Google Ads and Wildjar
     * Then calculating the CPC value for each campaign budget
     *
     * @param \Illuminate\Support\Collection $client
     * @return array
     */
    private function handleCPCs($client)
    {
        $dates = CarbonPeriod::create(today()->addDays(-7), today());

        [
            'adwords_ids' => $adwordsIds,
            'wildjar_id' => $wildjarId
        ] = $client;

        $cpcs = collect();

        $wildJarCalls = $this->handleWildJar($wildjarId, $dates);

        foreach ($dates as $date) {
            $df = $date->format('Y-m-d');
            $googleAdsSpending = $this->handleGoogleAds($adwordsIds, $date);
            $wildJarCall = $wildJarCalls[$df];

            $cpcs->push([
                'date' => $df,
                'data' => $googleAdsSpending->map(function($i) use ($wildJarCall) {
                    if ($wildJarCall < 1) $cpc = 0;
                    else $cpc = $i['spending'] / $wildJarCall;
                    return [
                        'account_id' => $i['account_id'],
                        'budget_id'  => $i['budget_id'],
                        'cpc'        => $cpc,
                        'calls'      => $wildJarCall,
                        'budget'     => $i['budget'],
                        'campaigns'  => $i['campaigns'],
                    ];
                })
            ]);
        }

        return [
            'campaigns' => count($googleAdsSpending),
            'cpcs'      => $cpcs,
        ];
    }

    /**
     * Fetch information from Google Ads
     * @param \Illuminate\Support\Collection $ids
     * @param \Carbon\Carbon $date
     * @return \Illuminate\Support\Collection
     */
    private function handleGoogleAds($ids, $date)
    {
        $campaigns = $this->fetchCampaigns($ids, $date);

        $result = collect();
        foreach ($campaigns->groupBy('budget_id') as $key => $campaigns) {
            $campaign = $campaigns[0];
            $result->push([
                'account_id' => $campaign['account_id'],
                'budget_id' => $key,
                'spending' => $campaigns->sum('spending'),
                'budget'  => $campaign['budget'],
                'campaigns' => $campaigns->pluck('name'),
            ]);
        }

        return $result;
    }

    /**
     * Fetch information from WildJar
     * @param string $id
     * @param \Carbon\CarbonPeriod $dates
     * @return array
     */
    private function handleWildJar($id, $dates)
    {
        $accounts = $this->fetchWJSubAccounts($id);

        $request = [
            'account'  => $accounts->join(','),
            'datefrom' => $dates->getStartDate()->startOfDay()->format('Y-m-d\TH:i:s'),
            'dateto'   => $dates->getEndDate()->endOfDay()->format('Y-m-d\TH:i:s'),
            'timezone' => 'Australia/Sydney',
            'dateview' => 'days',
        ];

        $summary = $this->wildJarClient->summary()->filter($request)['details'];

        $calls = [];

        foreach ($summary['label'] as $index => $value) {
            $calls[$value] = $summary['answered'][$index] + $summary['missed'][$index];
        }

        return $calls;
    }

    /**
     * Parse AdWords IDs from FreshSales accounts
     *
     * @param \Illuminate\Support\Collection $account
     * @return \Illuminate\Support\Collection
     */
    private function parseAdWordsIds($account)
    {
        $string = $account['custom_field']['cf_adwords_ids'];
        return Str::of($string)->replace('-', '')->explode("\n");
    }

    /**
     * Parse WildJar ID from FreshSales accounts
     *
     * @param \Illuminate\Support\Collection $account
     * @return array
     */
    private function parseWildJarId($account)
    {
        return $account['custom_field']['cf_wildjar_id'];
    }

    /**
     * Parse WildJar sub accounts from ID
     *
     * @param \Illuminate\Support\Collection $account
     * @return \Illuminate\Support\Collection
     */
    private function fetchWJSubAccounts($account)
    {
        if(!isset($wildJarAccounts))
            $this->wildJarAccounts = $this->wildJarClient->account()->all();

        $allAccountIds = $this->wildJarAccounts
            ->filter(function ($q) use ($account) {
                return $q['father'] == $account;
            })
            ->pluck('id');

        return $allAccountIds->push($account);
    }

    /**
     * Fetch campaigns from Google Ads
     * @param \Illuminate\Support\Collection $ids
     * @param \Carbon\Carbon $date
     * @return \Illuminate\Support\Collection
     */
    private function fetchCampaigns($ids, $date)
    {
        $query = 'SELECT campaign.name, metrics.cost_micros, campaign_budget.id, campaign_budget.amount_micros, campaign.advertising_channel_type FROM campaign' .
            ' WHERE segments.date = "' . $date->format('Y-m-d') . '"';

        $campaigns = collect();

        foreach ($ids as $id) {
            $stream = $this->googleAdsClient->search($id, $query);
            foreach ($stream->iterateAllElements() as $row) {

                if (!$this->passGoogleAdsFilter($row)) continue;

                $name = $row->getCampaign()->getName()->getValue();
                $budgetId = $row->getCampaignBudget()->getIdUnwrapped();
                $budget = $row->getCampaignBudget()->getAmountMicrosUnwrapped();
                $spending = $row->getMetrics()->getCostMicrosUnwrapped();

                $campaigns->push([
                    'name' => $name,
                    'spending' => $spending / 1000000,
                    'budget' => $budget / 1000000,
                    'budget_id' => $budgetId,
                    'account_id' => $id,
                ]);
            }
        }

        return $campaigns;
    }

    /**
     * Filter campaigns based on blacklist
     *
     * @param mixed $row
     * @return bool
     */
    private function passGoogleAdsFilter($row)
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
     * Calculate percentage change
     * @param float $old
     * @param float $new
     * @return int|float
     */
    private function relativeChange($old, $new)
    {
        return ($new - $old) / $old * 100;
    }
}
