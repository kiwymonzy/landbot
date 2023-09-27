<?php

namespace App\Http\Controllers;

use App\Library\FreshSales\FreshSales;
use Illuminate\Http\Request;

class FreshSalesController extends Controller
{
    public function account(Request $request)
    {
        [
            'manager' => $manager,
            'account' => $account,
        ] = $this->fetchAccountAndManager($request->phone);

        $res = [
            'name' => $account['name'],
            'manager' => [
                'name' => explode(' ', $manager['display_name'])[0],
                'email' => $manager['email']
            ]
        ];

        return $this->sendResponse('Account found!', $res);
    }

    private function fetchAccountAndManager($phone)
    {
        // Create FreshSales client
        $fs = new FreshSales();

        // Search for query in accounts
        $response = $fs->account()->lookup([
            'entities' => 'sales_account',
            'f' => 'cf_wa_number',
            'q' => $phone,
            'include' => 'owner',
        ]);

        if (count($response['sales_accounts']['sales_accounts']) == 0)
            abort(404, 'Account not found');

        return [
            'manager' => $response['sales_accounts']['users'][0],
            'account' => $response['sales_accounts']['sales_accounts'][0],
        ];
    }
}
