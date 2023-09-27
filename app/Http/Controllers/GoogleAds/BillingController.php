<?php

namespace App\Http\Controllers\GoogleAds;

use Google\Ads\GoogleAds\V3\Enums\MonthOfYearEnum\MonthOfYear;
use Illuminate\Http\Request;

class BillingController extends BaseController
{
    public function billing(Request $request)
    {
        $account = $this->fetchAccount($request->phone);
        $year = $request->year;
        $month = $request->month;
        $accountIndex = $request->account;

        $ids = $this->parseAdWordsIds($account, $year, $month);

        $urls = $this->fetchBilling($ids, $month, $year, $accountIndex);
    }

    public function fetchBilling($accountIds, $monthIndex, $yearIndex, $accountIndex)
    {
        $billingSetups = $this->fetchBillingSetups($accountIds);
        $month = $this->monthMapper($monthIndex);
        $year = $this->yearMapper($yearIndex);
        $account = $accountIndex - 1;

        $serviceClient = $this->adsClient()->getInvoiceServiceClient();

        $response = $serviceClient->listInvoices(
            $accountIds[$account],
            $billingSetups[$account],
            $year,
            $month,
        );

        dd($response);
    }

    public function fetchBillingSetups($accountIds)
    {
        $serviceClient = $this->adsClient()->getGoogleAdsServiceClient();

        $query = 'SELECT billing_setup.resource_name FROM billing_setup WHERE billing_setup.status = APPROVED';

        $billingIds = collect([]);

        foreach ($accountIds as $id) {
            $stream = $serviceClient->search($id, $query);
            foreach ($stream->iterateAllElements() as $row) {
                $billingId = $row->getBillingSetup()->getResourceName();
                $billingIds->push($billingId);
                break;
            }
        }

        return $billingIds;
    }

    /**
     * Map year index to string
     *
     * @param [type] $index
     * @return void
     */
    public function yearMapper($index)
    {
        $start = 2019;
        return strval($start + $index - 1);
    }

    /**
     * Map month index to string
     *
     * @param [type] $index
     * @return void
     */
    public function monthMapper($index)
    {
        switch($index) {
            case 1:
                return MonthOfYear::JANUARY;
            case 2:
                return MonthOfYear::FEBRUARY;
            case 3:
                return MonthOfYear::MARCH;
            case 4:
                return MonthOfYear::APRIL;
            case 5:
                return MonthOfYear::MAY;
            case 6:
                return MonthOfYear::JUNE;
            case 7:
                return MonthOfYear::JULY;
            case 8:
                return MonthOfYear::AUGUST;
            case 9:
                return MonthOfYear::SEPTEMBER;
            case 10:
                return MonthOfYear::OCTOBER;
            case 11:
                return MonthOfYear::NOVEMBER;
            case 12:
                return MonthOfYear::DECEMBER;
        }
    }
}
