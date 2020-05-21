<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FreshSalesController extends Controller
{
    public function account(Request $request)
    {
        [
            'users' => $manager,
            'sales_account' => $account,
        ] = $this->fetchAccount($request->phone, [
            'include' => 'owner'
        ]);

        $manager = $manager[0];

        $res = [
            'name' => $account['name'],
            'manager' => [
                'name' => explode(' ', $manager['display_name'])[0],
                'email' => $manager['email']
            ]
        ];

        return $this->sendResponse('Account found!', $res);
    }
}
