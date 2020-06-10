<?php

namespace App\Library\LandBot;

use Illuminate\Support\Facades\Http;
use App\Library\LandBot\Helpers\Customer;

class LandBot
{
    private $client;

    public function __construct()
    {
        // Retrieve environment variables
        $key = config('landbot.key');
        $url = config('landbot.url');

        $this->client = Http::withHeaders([
                'Authorization' => "Token $key"
            ])
            ->asJson()
            ->acceptJson()
            ->baseUrl($url);

        $this->customer = new Customer($this);
    }

    /**
     * Get customer instance
     *
     * @return App\Library\LandBot\Helpers\Customer
     */
    public function customer()
    {
        return $this->customer;
    }

    public function get($uri, $params = [])
    {
        return $this->client->get($uri, $params)->json();
    }

    public function post($uri, $body = [])
    {
        return $this->client->post($uri, $body)->json();
    }

    public function put($uri, $body = [])
    {
        return $this->client->put($uri, $body)->body();
    }
}
