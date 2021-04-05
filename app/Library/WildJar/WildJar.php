<?php

namespace App\Library\WildJar;

use App\Library\WildJar\Helpers\Account;
use App\Library\WildJar\Helpers\Call;
use App\Library\WildJar\Helpers\Summary;
use App\Models\Config;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class WildJar
{
    private $user;
    private $pass;
    private $url;
    private $token;
    private $client;
    private $call;
    private $summary;
    private $account;

    public function __construct()
    {
        $this->user = config('wildjar.user');
        $this->pass = config('wildjar.pass');
        $this->url = config('wildjar.url');

        $this->init();

        $this->client = Http::withHeaders([
            'Authorization' => "Bearer {$this->token}"
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

    public function init()
    {
        $token = Config::firstWhere('name', 'wildjar_token');
        $expiration = Carbon::parse($token->meta->expires_at);

        if (is_null($token) || now()->isAfter($expiration)) {
            $token = $this->authenticate();
        }

        $this->token = $token->value;
    }

    /**
     * Authenticate before making requests
     *
     * @return void
     */
    public function authenticate()
    {
        [
            'access_token' => $token,
            'expires_in' => $expires_in,
        ] = Http::withBasicAuth($this->user, $this->pass)
            ->post($this->url . 'token', [
                'grant_type' => 'client_credentials'
            ])
            ->json();

        $meta = [
            'expires_at' => now()->addSeconds($expires_in),
        ];

        return Config::updateOrCreate(['name' => 'wildjar_token'], [
            'value' => $token,
            'meta' => $meta,
        ]);
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
