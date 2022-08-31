<?php

namespace LibYear;

use GuzzleHttp\ClientInterface;

class Repository
{
	private string $url;
	private ?string $metadataUrl;
	private array $availablePackages;

	public function __construct(string $url, string $metadataUrl, array $availablePackages)
	{
		$this->url = $url;
		$this->metadataUrl = $this->fixMetadataUrl($metadataUrl);
		$this->availablePackages = $availablePackages;
	}

	private function fixMetadataUrl(?string $url): ?string
	{
		return strstr($url, '/p2');
	}

	public function hasPackage(string $package): bool
	{
		return in_array($package, $this->availablePackages);
	}

	public function getPackageUrl(string $package): string
	{
		return $this->url.str_replace('%package%', $package, $this->metadataUrl);
	}
}
