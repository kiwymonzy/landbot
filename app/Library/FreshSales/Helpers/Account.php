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

    public function search($query, $options = [])
    {
        $body = array_merge([
            "filter_rule" => $query
        ], $options);

        return collect($this->client->post('filtered_search/sales_account', $body))->recursive();
    }

    /**
     * Get account by id
     *
     * @param string $id
     * @return \Illuminate\Support\Collection
     */
    public function get($id, $params = [])
    {
        return collect($this->client->get("sales_accounts/$id", $params))->recursive();
    }
}
