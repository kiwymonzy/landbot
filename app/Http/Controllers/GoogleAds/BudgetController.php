<?php

namespace App\Http\Controllers\GoogleAds;

use App\Jobs\MutateCampaignBudget;
use Illuminate\Http\Request;

class BudgetController extends MutationController
{
    /**
     * Change selected campaign budget
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeBudget(Request $request)
    {
        $account = $this->fetchAccount($request->phone)['sales_account'];

        if (!$this->accountIsValid($account))
            abort(403, 'This feature is not enabled on your account');

        $adsAccountId = $this->parseAdWordsIds($account)[0];

        $campaigns = $this->fetchCampaigns($adsAccountId)->filter(function($i) {
            return $i['budget'] > 1;
        });

        $campaign = $this->formatCampaigns($campaigns)[$request->campaign - 1];

        $budget_old = $campaign['budget'];
        $budget_new = $budget_old + $this->formatAmount($request->amount);
        $delay = $this->durationMapper($request->duration);

        $this->mutateCampaign($adsAccountId, $campaign, $budget_new, $delay);

        return $this->sendResponse('', [
            'old_budget' => $budget_old,
            'new_budget' => $budget_new,
            'reverted' => $delay->format("l M d, Y h:ia"),
        ]);
    }

    private function formatAmount($amount)
    {
        if ($amount < 0) $amount *= -1;
        return $amount;
    }
}
