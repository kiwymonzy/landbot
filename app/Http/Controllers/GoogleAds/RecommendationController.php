<?php

namespace App\Http\Controllers\GoogleAds;

use App\Http\Controllers\Controller;
use App\Models\BudgetRecommendation;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RecommendationController extends MutationController
{
    /**
     * Show budget recommendation
     *
     * @param Request $request
     * @return void
     */
    public function show(Request $request)
    {
        $budgetRecommendation = $this->fetchRecommendation($request->phone);
        return $this->sendResponse('Latest recommendation', $budgetRecommendation);
    }

    /**
     * Accept budget recommendation
     *
     * @param Request $request
     * @return void
     */
    public function accept(Request $request)
    {
        $budgetRecommendation = $this->fetchRecommendation($request->phone);
        $delay = $this->durationMapper($request->duration);
        $budgetNew = $budgetRecommendation->budget + $this->formatAmount($request->amount);

        $this->mutateCampaign($budgetRecommendation, $budgetNew, $delay);

        $budgetRecommendation->status = BudgetRecommendation::ACCEPTED;
        $budgetRecommendation->save();

        return $this->sendResponse('', [
            'old_budget' => $budgetRecommendation->budget,
            'new_budget' => $budgetNew,
            'reverted' => $delay->format("l M d, Y h:ia"),
        ]);
    }

    /**
     * Decline budget recommendation
     *
     * @param Request $request
     * @return void
     */
    public function decline(Request $request)
    {
        $budgetRecommendation = $this->fetchRecommendation($request->phone);

        $budgetRecommendation->update([
            'status' => BudgetRecommendation::DECLINED
        ]);

        return $this->sendResponse('Successfully declined', $budgetRecommendation);
    }

    /**
     * Fetch latest budget recommendation from phone number
     *
     * @param string $phone
     * @return App\Models\BudgetRecommendation
     */
    private function fetchRecommendation($phone)
    {
        $client = Client::firstWhere('phone', 'like', '%' . $phone);

        $budgetRecommendation = $client->recommendations()
            ->where('status', BudgetRecommendation::PENDING)
            ->latest()
            ->first();

        if (is_null($budgetRecommendation))
            abort(204);

        return $budgetRecommendation;
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
     * @return Carbon\Carbon
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
}
