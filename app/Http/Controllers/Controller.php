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
     * @param String $channel
     * @return \Illuminate\Support\Collection
     */
    public function fetchAccount(String $number, $account_params = [])
    {
        // Create FreshSales client
        $fs = new FreshSales();

        // Search for number name in accounts
        $accounts = $fs->account()->search($number);

        // Find first exact match in number name custom field
        foreach ($accounts as $account) {
            $match = $account['more_match'];
            if (
                $match['field_name'] == 'WA Number' &&
                $match['field_value'] == $number
            ) {
                $account = $fs->account()->get($account['id'], $account_params);

                $this->makeModel($account['sales_account']);

                return $account;
            }
        }

        // Throw error when none found
        Log::error('Phone number not found', [
            'number' => $number,
            'search_results' => $accounts
        ]);

        abort(404, 'Phone number not found');
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
