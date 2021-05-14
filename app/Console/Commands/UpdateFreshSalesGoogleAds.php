<?php

namespace App\Console\Commands;

use App\Library\FreshSales\FreshSales;
use App\Models\FreshSalesGoogleAds;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class UpdateFreshSalesGoogleAds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:fresh-sales-google-ads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update FreshSalesGoogleAds table data';

    protected $freshClient;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->freshClient = new FreshSales();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $totalPages = 1;
        $page = 1;

        while ($page <= $totalPages) {
            $search = $this->freshClient->account()->index([
                'include' => 'owner,industry_type',
                'per_page' => 100,
                'page' => $page,
            ]);

            $totalPages = $search['meta']['total_pages'];
            $owners = $search['users']->groupBy('id')->map(fn($users) => $users->first()['display_name']);
            $industries = $search['industry_types']->groupBy('id')->map(fn($industries) => $industries->first()['name']);

            foreach ($search['sales_accounts'] as $account) {
                $name = $account['name'];
                $owner = $owners[$account['owner_id']];
                $industry = $industries[$account['industry_type_id']] ?? '';

                $googleAdsIds = $account['custom_field']['cf_adwords_ids'];
                if (is_null($googleAdsIds)) {
                    FreshSalesGoogleAds::firstOrCreate([
                        'account_name' => $name,
                        'account_manager' => $owner,
                        'industry' => $industry,
                        'mcc_id' => null,
                    ]);
                } else {
                    $googleAdsIds = Str::of($googleAdsIds)->replace('-', '')->explode("\n");
                    foreach ($googleAdsIds as $googleAdsId) {
                        FreshSalesGoogleAds::firstOrCreate([
                            'account_name' => $name,
                            'account_manager' => $owner,
                            'industry' => $industry,
                            'mcc_id' => (int) $googleAdsId,
                        ]);
                    }
                }
            }
            dump("{$page}/{$totalPages}");
            $page += 1;
        }

        return 0;
    }
}
