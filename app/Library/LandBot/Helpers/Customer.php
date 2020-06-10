<?php

namespace App\Library\LandBot\Helpers;

use App\Library\LandBot\LandBot;

class Customer
{
    private $client;

    public function __construct(LandBot $client)
    {
        $this->client = $client;
    }

    /**
     * Search landbot customers by field and value
     *
     * @param string $field
     * @param string $value
     * @return array
     */
    public function searchBy($field, $value)
    {
        return $this->client->get('customers/', [
            'search_by' => $field,
            'search' => $value
        ]);
    }

    public function sendMessage($customerId, $message)
    {
        return $this->client->post("customers/$customerId/send_text/", [
            'message' => $message
        ]);
    }

    public function assignBot($customerId, $botId)
    {
        $token = config('landbot.key');

        $request = curl_init();
        curl_setopt($request, CURLOPT_HTTPHEADER, [
            "Authorization: Token $token",
        ]);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($request, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($request, CURLOPT_URL, "https://api.landbot.io/v1/customers/$customerId/assign_bot/$botId/");

        $res = curl_exec($request);

        curl_close($request);
        return json_decode($res);
    }
}
