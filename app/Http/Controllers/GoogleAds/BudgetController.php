<?php

namespace App\Http\Controllers\GoogleAds;

class BudgetController extends BaseController
{
    public function fetchBudgets(array $accountIds)
    {
        $serviceClient = $this->adsClient->getGoogleAdsServiceClient();

        $query = 'SELECT campaign_budget.amount_micros FROM campaign';

        $budgets = collect([]);

        foreach ($accountIds as $id) {
            $sum = 0;
            $stream = $serviceClient->search($id, $query);
            foreach ($stream->iterateAllElements() as $row) {
                $sum += $row->getMetrics()->getCostMicrosUnwrapped();
            }
            $budgets->push($sum);
        }

        return $budgets->map(function ($item) {
            return $item / 1000000;
        });
    }
}
