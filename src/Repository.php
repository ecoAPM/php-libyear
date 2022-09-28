<?php

namespace LibYear;

class Repository
{
	public string $url;
	public string $metadata_pattern;

	public function __construct(string $url, string $metadata_pattern)
	{
		$this->url = $url;
		$this->metadata_pattern = $metadata_pattern;
	}

	public function getMetadataURL(string $package): string
	{
		return $this->url . str_replace("%package%", $package, $this->metadata_pattern);
	}
}