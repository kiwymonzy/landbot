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
        $accounts = $fs->account()->search([
            [
                "attribute" => $field,
                "operator" => "contains_any",
                "value" => [$query],
            ],
        ]);

        $results = $accounts['sales_accounts'];

        // Throw error when none found
        if ($results->isEmpty()) {
            Log::error('Account not found', [
                'query' => $query,
                'search_results' => $accounts
            ]);

            abort(404, 'Account not found');
        }

        // Use first result as account
        $account = $results[0];
        $account = $fs->account()->get($account['id'], $account_params);

        $this->makeModel($account['sales_account']);
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
