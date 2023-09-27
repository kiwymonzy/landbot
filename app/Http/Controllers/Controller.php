<?php

namespace App\Http\Controllers;

use App\Library\FreshSales\FreshSales;
use App\Library\Utils\ResponseUtil;
use App\Models\Client;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Send success response
     *
     * @param string $message
     * @param mixed $data
     * @param integer $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendResponse(string $message, $data, $code = 200)
    {
        $res = ResponseUtil::makeResponse($message, $data);

        return response()->json($res, $code);
    }

    /**
     * Send error response
     *
     * @param string $message
     * @param integer $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendError(string $message, $code = 400)
    {
        $res = ResponseUtil::makeError($message);

        return response()->json($res, $code);
    }

    /**
     * Fetch account from FreshSales API
     *
     * @param String $query
     * @return \Illuminate\Support\Collection
     */
    public function fetchAccount(String $query, $account_params = [], $field = 'cf_wa_number')
    {
        // Create FreshSales client
        $fs = new FreshSales();

        // Search for query in accounts
        $search_params = array_merge([
            'entities' => 'sales_account',
            'q' => $query,
            'f' => $field,
        ], $account_params);
        $response = $fs->account()->lookup($search_params);

        $accounts = $response['sales_accounts']['sales_accounts'];

        // Throw error when none found
        if (count($accounts) == 0) {
            Log::error('Account not found', [
                'query' => $query,
                'search_results' => $response
            ]);

            abort(404, 'Account not found');
        }

        // Return first account
        $account = $accounts[0];

        $this->makeModel($account);
        return $account;
    }

    private function makeModel($account)
    {
        Client::updateOrCreate(
            ['freshsales_id' => $account['id']],
            [
                'company' => $account['name'],
                'phone' => $account['custom_field']['cf_wa_number'],
            ]
        );
    }
}
