<?php

namespace App\Library\WildJar\Helpers;

use App\Library\WildJar\WildJar;

class Account
{
    private $client;

    public function __construct(WildJar $client)
    {
        $this->client = $client;
    }

    /**
     * Get all accounts
     *
     * @return \Illuminate\Support\Collection
     */
    public function index()
    {
        return collect($this->client->get('account')['data'])->recursive();
    }

    /**
     * Filter all accounts
     *
     * @param array $params
     * @return void
     */
    public function filter($params = [])
    {
        return collect($this->client->get('account', $params)['data'])->recursive();
    }

    public function all()
    {
        return collect($this->client->get('account/dash/FORCE')['data'])->recursive();
    }
}
