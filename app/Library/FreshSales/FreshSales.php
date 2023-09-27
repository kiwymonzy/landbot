<?php

namespace App\Library\FreshSales;

use Illuminate\Support\Facades\Http;
use App\Library\FreshSales\Helpers\Account;

class FreshSales
{
    private $url;
    private $client;
    private Account $account;

    public function __construct()
    {
        // Retrieve environment variables
        $key = config('freshsales.key');
        $url = config('freshsales.url');

        $this->client = Http::withHeaders([
            'Authorization' => "Token token=$key"
        ])
        // ->withOptions(['debug' => true])
        ;
        $this->url = $url;

        $this->account = new Account($this);
    }

    public function account()
    {
        return $this->account;
    }

    public function get($uri, $params = [])
    {
        return $this->client->get($this->url . $uri, $params)->json();
    }

    public function post($uri, $params = [])
    {
        return $this->client->post($this->url . $uri, $params)->json();
    }
}
