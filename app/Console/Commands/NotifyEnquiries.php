<?php

namespace App\Console\Commands;

use App\Library\FreshSales\FreshSales;
use App\Library\GoogleAds\GoogleAds;
use App\Library\LandBot\LandBot;
use App\Library\WildJar\WildJar;
use ErrorException;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use LaravelAds\LaravelAds;

class NotifyEnquiries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:enquiries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify accounts with flag enabled on Fresh';

    /**
     * @var \App\Library\FreshSales\Helpers\Account
     */
    private $freshClient;

    /**
     * @var \App\Library\LandBot\Helpers\Customer
     */
    private $landbotClient;

    /**
     * @var \Google\Ads\GoogleAds\V14\Services\GoogleAdsServiceClient
     */
    private $adsClient;

    /**
     * @var \App\Library\WildJar\WildJar
     */
    private $wildjarClient;

    /**
     * @var Carbon
     */
    private $currentTime;

    /**
     * @var string
     */
    private $formattedTime;

    /**
     * @var string
     */
    private $formattedDateTime;

    /**
     * @var bool
     */
    private $hasErrors;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->freshClient = (new FreshSales)->account();
        $this->landbotClient = (new LandBot)->customer();
        $this->adsClient = (new GoogleAds())
            ->client()
            ->getGoogleAdsServiceClient();
        $this->wildjarClient = new WildJar;
        $this->currentTime = now();
        $this->formattedTime = $this->currentTime->format('H:i');
        $this->formattedDateTime = $this->currentTime->format('d M y, H:i');
        $this->hasErrors = false;
    }

    /**
     * Execute the console command.
     *
     * @return bool
     */
    public function handle()
    {
        // Search
        $accounts = $this->freshClient->search([
            [
                'attribute' => 'cf_whatsapp_hourly_updates',
                'operator' => 'is_any',
                'value' => ['true'],
            ],
            [
                'attribute' => 'cf_whatsapp_hourly_updates__days',
                'operator' => 'contains',
                'value' => $this->currentTime->format('l'),
            ],
            [
                'attribute' => 'cf_whatsapp_hourly_updates__times',
                'operator' => 'contains',
                'value' => $this->currentTime->format('G:00'),
            ],
        ])['sales_accounts'];

        // Process
        $accounts->each(function ($acc) {
            try {

                // Get full fresh account
                [
                    'id' => $freshId,
                ] = $acc;
                [
                    'sales_account' => [
                        'custom_field' => [
                            'cf_wa_number' => $freshWaNumber,
                            'cf_adwords_ids' => $freshAdwordsIds,
                            'cf_bing_ads_ids' => $freshBingAdsIds,
                            'cf_wildjar_id' => $freshWildjarId,
                        ]
                    ]
                ] = $this->freshClient->get($freshId);

                $freshWaNumber = preg_replace('/[^0-9\-]/', '', $freshWaNumber);

                [
                    'name' => $name
                ] = $acc;

                // Retrieve spending and calls
                [
                    'google' => $googleSpend,
                    'bing' => $bingSpend,
                ] = $this->fetchSpend($freshAdwordsIds, $freshBingAdsIds);
                $googleSpendFormatted = currencyFormat($googleSpend);
                $bingSpendFormatted = currencyFormat($bingSpend);
                [
                    'google' => $googleCalls,
                    'bing' => $bingCalls,
                ] = $this->fetchCalls($freshWildjarId);

                // Calculate cost per enquiry
                $googleCpe = is_null($googleSpend)
                    ? $googleSpend
                    : $this->calcCpe($googleSpend, $googleCalls);
                $bingCpe = is_null($bingSpend)
                    ? $bingSpend
                    : $this->calcCpe($bingSpend, $bingCalls);

                // Formatted result
                $googleRes = is_null($googleCpe)
                    ? 'Not Active'
                    : "Cost: {$googleSpendFormatted}, Leads: {$googleCalls}, Cost Per Lead: {$googleCpe}";
                $bingRes = is_null($bingCpe)
                    ? 'Not Active'
                    : "Cost: {$bingSpendFormatted}, Leads: {$bingCalls}, Cost Per Lead: {$bingCpe}";

                // Call Zapier
                Http::post('https://hooks.zapier.com/hooks/catch/4537599/31wpt10/', [
                    'name' => $name,
                    'wa_number' => $freshWaNumber,
                    'datetime' => $this->formattedDateTime,
                    'google' => [
                        'spend' => $googleSpendFormatted,
                        'calls' => $googleCalls,
                        'cpe' => $googleCpe,
                    ],
                    // 'bing' => [
                    //     'spend' => $bingSpendFormatted,
                    //     'calls' => $bingCalls,
                    //     'cpe' => $bingCpe,
                    // ],
                ]);
            } catch (\Exception $e) {
                Log::error("An error occurred: " . $e->getMessage());
            }
        });

        return (int) $this->hasErrors;
    }

    /**
     *
     * @param string $adwordsIds
     * @param string $bingIds
     * @return mixed
     */
    private function fetchSpend($adwordsIds, $bingIds)
    {
        return [
            'google' => $this->fetchGoogleSpend($adwordsIds),
            'bing' => $this->fetchBingSpend($bingIds),
        ];
    }

    /**
     * Fetch google ads spend
     *
     * @param string $adwordsIds
     * @return int
     */
    private function fetchGoogleSpend($adwordsIds)
    {
        $ids = $this->parseAdWordsIds($adwordsIds);

        if ($ids->isEmpty()) return null;

        $date = 'TODAY';
        $query = 'SELECT metrics.cost_micros FROM customer WHERE segments.date DURING ' . $date;

        $result = collect();

        foreach ($ids as $id) {
            $spend = 0;
            $stream = $this->adsClient->search($id, $query);
            foreach ($stream->iterateAllElements() as $row) {
                $spend += $row->getMetrics()->getCostMicros();
            }
            $result->push($spend / 1000000);
        }

        return $result->sum();
    }

    private function fetchBingSpend($bingIds)
    {
        $ids = $this->parseBingIds($bingIds);

        if ($ids->isEmpty()) return null;

        $dates = [
            'from' => $this->currentTime->copy()->startOfDay()->format('Y-m-d'),
            'to' => $this->currentTime->copy()->endOfDay()->format('Y-m-d'),
        ];

        $spend = 0;

        foreach ($ids as $id) {
            $report = LaravelAds::bingAds()
                ->with($id)
                ->reports($dates['from'], $dates['to'])
                ->getAccountReport();
            $spend += $report->sum('cost');
        }

        return $spend;
    }

    /**
     * Fetch number of calls
     *
     * @param string $wildjarId
     * @return int
     */
    private function fetchCalls($wildjarId)
    {
        $ids = $this->parseWildJarId($wildjarId);

        $accountIds = array_filter($ids->pluck('account')->toArray());
        $accountString = implode(',', $accountIds);

        $data = [
            'account' => $accountString,
            'datefrom' => $this->currentTime->copy()->startOfDay()->format('Y-m-d\TH:i:s'),
            'dateto' => $this->currentTime->copy()->endOfDay()->format('Y-m-d\TH:i:s'),
            'timezone' => 'Australia/Sydney',
        ];

        $calls = $this->wildjarClient
            ->call()
            ->filter($data)
            ->groupBy('web.source')
            ->map(fn ($item) => $item->count());

        return [
            'google' => $calls['google'] ?? 0,
            'bing' => $calls['bing'] ?? 0,
        ];
    }

    /**
     * Parse adwords ids
     *
     * @param string $adwordsIds
     * @return Collection
     */
    private function parseAdWordsIds($adwordsIds)
    {
        return Str::of($adwordsIds)->replace('-', '')->explode("\n")->filter();
    }

    /**
     * Parse bing ids
     *
     * @param string $bingIds
     * @return Collection
     */
    private function parseBingIds($bingIds)
    {
        return Str::of($bingIds)->explode("\n")->filter();
    }

    /**
     * Parse wildjar id
     *
     * @param string $wildjarId
     * @return Collection
     */
    private function parseWildJarId($wildjarId)
    {
        $accountDetails = $this->wildjarClient->account()->show($wildjarId);

        $childAccountIds = $accountDetails['children'];

        if (!$childAccountIds) return collect([$wildjarId]);

        return $childAccountIds->push($wildjarId);
    }

    /**
     * Calculate cost per enquiry
     *
     * @param int $spending
     * @param int $calls
     * @return string
     */
    private function calcCpe($spending, $calls)
    {
        return currencyFormat($spending / ($calls ?: 1));
    }
}
