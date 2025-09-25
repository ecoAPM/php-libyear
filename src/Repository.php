<?php

namespace ecoAPM\LibYear;

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
		$url_info = parse_url($this->url);
		$metadata_info = parse_url($this->metadata_pattern);

		$scheme = $metadata_info['scheme'] ?? $url_info['scheme'];
		$host = $metadata_info['host'] ?? $url_info['host'];
		$path = str_replace("%package%", $package, $metadata_info["path"]);

		return "$scheme://$host$path";
	}
}
