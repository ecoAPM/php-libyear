<?php

namespace ecoAPM\LibYear;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class RepositoryAPI
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

	public function getInfo(string $url, bool $verbose): ?Repository
	{
		try {
			$response = $this->http_client->request('GET', "$url/packages.json");
			$result = json_decode($response->getBody()->getContents(), true) ?? [];
			return new Repository($url, array_key_exists('metadata-url', $result) ? $result['metadata-url'] : null);
		} catch (GuzzleException $e) {
			if ($verbose) {
				fwrite($this->stderr, "Could not create repository for $url\n");
			}
			return null;
		}
	}

	public function getPackageInfo(string $package, Repository $repository, bool $verbose): array
	{
		try {
			$url = $repository->getMetadataURL($package);
			$response = $this->http_client->request('GET', $url);
			$json = json_decode($response->getBody()->getContents(), true);
			return $json['packages'][$package] ?? [];
		} catch (GuzzleException $e) {
			if ($verbose) {
				fwrite($this->stderr, "Could not find info for $package on $repository->url\n");
			}
			return [];
		}
	}
}
