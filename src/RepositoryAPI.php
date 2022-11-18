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
			return new Repository($url, $result);
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
			$url = $repository->metadata_pattern != null
				? $repository->getMetadataURL($package)
				: $this->getMetadataURLFromProviders($package, $repository);
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

	private function getMetadataURLFromProviders(string $package, Repository $repository): string
	{
		foreach ($repository->providers as $provider) {
			$hash = $this->getHashFromProvider($package, $provider);
			if ($hash != null) {
				return $repository->getProvidersURL($package, $hash);
}
		}
		return '';
	}

	private function getHashFromProvider(string $package, string $url): ?string
	{
		try {
			$response = $this->http_client->request('GET', $url);
			$json = json_decode($response->getBody()->getContents(), true);

			return array_key_exists($package, $json['providers'])
				? $json['providers'][$package]['sha256']
				: null;

		} catch (GuzzleException $e) {
			return null;
		}
	}
}
