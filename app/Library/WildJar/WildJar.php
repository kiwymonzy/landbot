<?php

namespace App\Library\WildJar;

use App\Library\WildJar\Helpers\Account;
use App\Library\WildJar\Helpers\Call;
use App\Library\WildJar\Helpers\Summary;
use Illuminate\Support\Facades\Http;

class WildJar
{
    private $user;
    private $pass;
    private $url;
    private $client;
    private $call;
    private $summary;
    private $account;

    public function __construct()
    {
        $this->user = config('wildjar.user');
        $this->pass = config('wildjar.pass');
        $this->url = config('wildjar.url');

        $token = $this->authenticate();
        $this->client = Http::withHeaders([
            'Authorization' => "Bearer $token"
        ]);

        $this->call = new Call($this);
        $this->summary = new Summary($this);
        $this->account = new Account($this);
    }

    /**
     * Return Call object
     *
     * @return \App\Library\WildJar\Helpers\Call
     */
    public function call()
    {
        return $this->call;
    }

    /**
     * Return Summary object
     *
     * @return \App\Library\WildJar\Helpers\Summary
     */
    public function summary()
    {
        return $this->summary;
    }

    /**
     * Return Account object
     *
     * @return \App\Library\WildJar\Helpers\Account
     */
    public function account()
    {
        return $this->account;
    }

    /**
     * Authenticate before making requests
     *
     * @return void
     */
    public function authenticate()
    {
        return Http::withBasicAuth($this->user, $this->pass)
            ->post($this->url . 'token', [
                'grant_type' => 'client_credentials'
            ])->json()['access_token'];
    }

    /**
     * Execute get request
     *
     * @param string $uri
     * @param array $params
     * @return array
     */
    public function get($uri, $params = [])
    {
        return $this->client->get($this->url . $uri, $params)->json();
    }
}
