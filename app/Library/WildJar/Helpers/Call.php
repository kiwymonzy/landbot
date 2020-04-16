<?php

namespace App\Library\WildJar\Helpers;

use App\Library\WildJar\WildJar;

class Call
{
    private $client;

    public function __construct(WildJar $client)
    {
        $this->client = $client;
    }

    /**
     * Get all calls
     *
     * @return \Illuminate\Support\Collection
     */
    public function index()
    {
        return collect($this->client->get('call')['data'])->recursive();
    }

    public function filter($params = [])
    {
        return collect($this->client->get('call', $params)['data'])->recursive();
    }
}
