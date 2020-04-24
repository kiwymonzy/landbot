<?php

namespace App\Http\Controllers\GoogleAds;

use Illuminate\Http\Request;

class AccountController extends BaseController
{
    public function accounts(Request $request)
    {
        $account = $this->fetchAccount($request->phone)['sales_account'];

        $ids = $this->parseAdWordsIds($account);

        $accounts = $this->fetchAdsAccounts($ids);

        return $this->sendResponse('', $accounts);
    }

    public function fetchAdsAccounts(Array $accountIds)
    {
        $serviceClient = $this->adsClient()->getGoogleAdsServiceClient();

        $query = 'SELECT customer.descriptive_name FROM customer ORDER BY customer.descriptive_name';

        $names = collect([]);

        foreach ($accountIds as $id) {
            $stream = $serviceClient->search($id, $query);
            foreach ($stream->iterateAllElements() as $row) {
                $name = $row->getCustomer()->getDescriptiveName()->getValue();
                $names->push($name);
            }
        }

        return $names;
    }
}
