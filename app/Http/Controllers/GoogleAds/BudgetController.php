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
        $account = $this->fetchAccount($request->phone);

        if (!$this->accountIsValid($account))
            abort(403, 'This feature is not enabled on your account');

        $campaigns = $this->fetchActiveCampaigns($account);

        $campaign = $this->formatCampaigns($campaigns)[$request->campaign - 1];

        $budgetOld = $campaign['budget'];
        $budgetNew = $budgetOld + $this->formatAmount($request->amount);
        $delay = $this->durationMapper($request->duration);

        $this->mutateCampaign($campaign, $budgetNew, $delay['date']);
        $this->storeMutation($account['id'], $campaign, $this->formatAmount($request->amount), $delay);

        return $this->sendResponse('', [
            'old_budget' => $budgetOld,
            'new_budget' => $budgetNew,
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
     * 5. Next 30 Days
     *
     * @param int $index
     * @return array
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
            case 5:
                $date->addDays(30);
                $name = 'Next 30 Days';
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

    private function storeMutation($account, $campaign, $adjust, $delay)
    {
        $amountOld = $campaign['budget'];
        $amountNew = $amountOld + $adjust;
        $budgetMutation = BudgetMutation::make([
            'amount_old'    => $amountOld,
            'amount_adjust' => $adjust,
            'amount_new'    => $amountNew,
            'campaign'      => explode(' $', $campaign['string'])[0],
            'date_name'     => $delay['name'],
            'date_revert'   => $delay['date'],
        ]);
        $budgetMutation->client()->associate(
            Client::firstWhere('freshsales_id', $account)
        );
        $budgetMutation->save();
    }
}
