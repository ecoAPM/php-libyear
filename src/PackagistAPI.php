<?php

namespace LibYear;

use GuzzleHttp\ClientInterface;

class PackagistAPI
{
    private ClientInterface $http_client;

    public function __construct(ClientInterface $http_client)
    {
        $this->http_client = $http_client;
    }

    public function getPackageInfo(string $package): array
    {
        $response = $this->http_client->request('GET', "https://repo.packagist.org/packages/{$package}.json");
        return json_decode($response->getBody()->getContents(), true) ?? [];
    }
}