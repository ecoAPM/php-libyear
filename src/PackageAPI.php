<?php

namespace LibYear;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class PackageAPI
{
	private ClientInterface $http_client;

	public function __construct(ClientInterface $http_client, $stderr)
	{
		$this->http_client = $http_client;
		$this->stderr = $stderr;
	}

	public function getPackageInfo(string $name, string $packageUrl): array
	{
		try {
			$response = $this->http_client->request('GET', $packageUrl);
			$result = json_decode($response->getBody()->getContents(), true) ?? [];
			if (isset($result['packages'][$name]) && !empty($result['packages'][$name])) {
				return array_column($result['packages'][$name], null, 'version');
			}

		} catch (GuzzleException $guzzleException) {
			fwrite($this->stderr, "Could not find info for {$packageUrl}\n");
		}

		return [];
	}

	public function getRepositoryInfo(string $repositoryUrl): ?Repository
	{
		try {
			$response = $this->http_client->request('GET', $repositoryUrl . "/packages.json");
			$result = json_decode($response->getBody()->getContents(), true) ?? [];

			if (isset($result['metadata-url'])) {
				return new Repository(
					$repositoryUrl,
					$result['metadata-url'],
					$result['available-packages'] ?? []
				);
			}
		} catch (GuzzleException $guzzleException) {
			fwrite($this->stderr, "Could not find info for {$repositoryUrl}\n");
		}

		return null;
	}
}
