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

        $response = clock()->event('Search account')->run(fn() => $this->client->post('filtered_search/sales_account', $body));

        return collect($response);
    }

    public function index($params = [])
    {
        return collect($this->client->get("sales_accounts/view/9000586651", $params))->recursive();
    }

    /**
     * Get account by id
     *
     * @param string $id
     * @return \Illuminate\Support\Collection
     */
    public function get($id, $params = [])
    {
        $response = clock()->event('Get account')->run(fn() => $this->client->get("sales_accounts/$id", $params));

        return collect($response)->recursive();
    }

    public function lookup($query)
    {
        $response = clock()->event('Lookup account')->run(fn() => $this->client->get("lookup", $query));

        return collect($response)->recursive();
    }
}
