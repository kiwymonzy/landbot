<?php

namespace App\Http\Controllers;

use App\Library\FreshSales\FreshSales;
use App\Library\Utils\ResponseUtil;
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
     * @return string
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
     * @return void
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
        foreach ($accounts as $acc) {
            $match = $acc['more_match'];
            if ($match['field_name'] == 'WA Number' && $match['field_value'] == $number) {
                return $fs->account()->get($acc['id'], $account_params);
            }
        }

        // Throw error when none found
        abort(404, 'Phone number not found');
    }
}
