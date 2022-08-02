<?php

namespace LibYear;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class PackagistAPI
{
	private ClientInterface $http_client;

	/** @var resource */
	private $stderr;

	/**
	 * @param ClientInterface $http_client
	 * @param resource $stderr
	 */
	public function __construct(ClientInterface $http_client, $stderr)
	{
		$this->http_client = $http_client;
		$this->stderr = $stderr;
	}

	public function getPackageInfo(string $package): array
	{
		try {
			$response = $this->http_client->request('GET', "https://repo.packagist.org/packages/{$package}.json");
			return json_decode($response->getBody()->getContents(), true) ?? [];
		} catch (GuzzleException $e) {
			fwrite($this->stderr, "Could not find info for {$package} on Packagist\n");
			return [];
		}
	}
}
