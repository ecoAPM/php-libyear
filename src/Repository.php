<?php

namespace LibYear;

use GuzzleHttp\ClientInterface;

class Repository
{
	private string $url;
	private ?string $metadata_url;
	private array $available_packages;

	public function __construct(string $url, string $metadata_url, array $available_packages)
	{
		$this->url = $url;
		$this->metadata_url = $this->fixMetadataUrl($metadata_url);
		$this->available_packages = $available_packages;
	}

	private function fixMetadataUrl(?string $url): ?string
	{
		return preg_replace('/.*\/p2/', '/p2', $url);
	}

	public function hasPackage(string $package): bool
	{
		return empty($this->available_packages) || in_array($package, $this->available_packages);
	}

	public function getPackageUrl(string $package): string
	{
		return $this->url.str_replace('%package%', $package, $this->metadata_url);
	}
}
