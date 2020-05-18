<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class FreshSalesController extends Controller
{
    public function account(Request $request)
    {
        $account = $this->fetchAccount($request->phone, [
            'include' => 'owner'
        ]);

        $manager = $account['users'][0];
        $account = $account['sales_account'];

        $res = [
            'name' => $account['name'],
            'manager' => [
                'name' => explode(' ', $manager['display_name'])[0],
                'email' => $manager['email']
            ]
        ];

        Client::firstOrCreate(
            ['freshsales_id' => $account['id']],
            [
                'company' => $account['name'],
                'phone' => $account['custom_field']['cf_wa_number'],
            ]
        );

        return $this->sendResponse('Account found!', $res);
    }
}
