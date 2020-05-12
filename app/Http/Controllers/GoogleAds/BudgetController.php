<?php

namespace App\Http\Controllers\GoogleAds;

use App\Jobs\MutateCampaignBudget;
use Illuminate\Http\Request;

class BudgetController extends MutationController
{
    public function currentBudget(Request $request)
    {
        $account = $this->fetchAccount($request->phone)['sales_account'];

        if(!$this->accountIsValid($account))
            abort(403, 'This feature is not enabled on your account');

        $id = $this->parseAdWordsIds($account)[0];
        $id = "5151053398";

        $campaigns = $this->fetchCampaigns($id);

        $res = [
            'name' => $account['name'],
            'current_budget' => priceFormat($campaigns->sum('budget')),
            'campaigns' => $this->formatCampaigns($campaigns),
        ];

        return $this->sendResponse('', $res);
    }

    public function changeBudget(Request $request)
    {
        $account = $this->fetchAccount($request->phone)['sales_account'];

        if (!$this->accountIsValid($account))
            abort(403, 'This feature is not enabled on your account');

        $adsAccountId = $this->parseAdWordsIds($account)[0];
        $adsAccountId = "5151053398";

        $campaign = $this->formatCampaigns($this->fetchCampaigns($adsAccountId))[$request->campaign - 1];

        dd($campaign);

        $amountOld = $campaign['budget'];
        $amountNew = $campaign['budget'] + $this->formatAmount($request->amount);

        MutateCampaignBudget::dispatch($adsAccountId, $campaign['budget_id'], $amountNew);
        MutateCampaignBudget::dispatch($adsAccountId, $campaign['budget_id'], $amountOld)
            ->delay($this->durationMapper($request->campaign));

        return $this->sendResponse('', [
            'campaign' => $campaign['name'],
            'old_budget' => $amountOld,
            'new_budget' => $amountNew,
        ]);
    }

    private function formatAmount($amount)
    {
        if ($amount < 0) $amount *= -1;
        return $amount;
    }

    private function formatCampaigns($campaigns)
    {
        $res = collect();

        foreach ($campaigns->groupBy('budget_id') as $id => $items) {
            $val = [
                'budget_id' => $id,
                'budget' => $items[0]['budget'],
                'items' => $items
            ];

            if ($items->count() > 1) {
                $val['string'] = $items->pluck('name')->map(function ($i) {
                    return '(' . $i . ')';
                })->join('') . ' $' . $items[0]['budget'];
            } else {
                $val['string'] = $items[0]['name'] . ' $' . $items[0]['budget'];
            }

            $res->push($val);
        }

        return $res;
    }
}
