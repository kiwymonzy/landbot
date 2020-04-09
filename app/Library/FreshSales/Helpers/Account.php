<?php

namespace App\Library\FreshSales\Helpers;

use App\Library\FreshSales\FreshSales;

class Account
{
    private $client;

    public function __construct(FreshSales $client)
    {
        $this->client = $client;
    }

    public function search($query)
    {
        $params = [
            'q' => $query,
            'include' => 'sales_account',
            'per_page' => 100,
        ];

        return collect($this->client->get('search', $params))->recursive();
    }

    /**
     * Get account by id
     *
     * @param string $id
     * @return \Illuminate\Support\Collection
     */
    public function get($id)
    {
        return collect($this->client->get("sales_accounts/$id")['sales_account'])->recursive();
    }
}
