<?php

namespace App\Http\Controllers\GoogleAds;

use App\Jobs\MutateCampaignBudget;
use App\Models\BudgetMutation;
use App\Models\Client;
use Carbon\Carbon;
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
        $this->storeMutation($account['id'], $campaign, $this->formatAmount($request->amount), $delay);

        return $this->sendResponse('', [
            'old_budget' => $budget_old,
            'new_budget' => $budget_new,
            'reverted' => $delay->format("l M d, Y h:ia"),
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
     * 5. Next 30 Days
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
                $date->addDays(7);
                break;
            case 5:
                $date->addDays(30);
                break;
            default:
                $date->addDay();
                break;
        }

        return $date->setTime(9, 0);
    }

    private function formatAmount($amount)
    {
        if ($amount < 0) $amount *= -1;
        return $amount;
    }

    private function storeMutation($account, $campaign, $adjust, $revert)
    {
        $amount_old = $campaign['budget'];
        $amount_new = $amount_old + $adjust;
        $status = BudgetMutation::make([
            'amount_old'    => $amount_old,
            'amount_adjust' => $adjust,
            'amount_new'    => $amount_new,
            'campaign'      => explode(' $', $campaign['string'])[0],
            'date_revert'   => $revert,
        ]);
        $status->client()->associate(
            Client::firstWhere('freshsales_id', $account)
        );
        $status->save();
    }
}
