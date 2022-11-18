<?php

namespace ecoAPM\LibYear;

class Repository
{
	public string $url;
	public ?string $metadata_pattern;
	public ?string $providers_pattern;

	/** @var string[] */
	public array $providers;

	public function __construct(string $url, array $result)
	{
		$this->url = $url;

		$this->metadata_pattern = array_key_exists('metadata-url', $result)
			? $result['metadata-url']
			: null;

		$this->providers_pattern = array_key_exists('providers-url', $result)
			? $result['providers-url']
			: null;

		$this->providers = array_key_exists('provider-includes', $result)
			? self::mapProviders($url, $result['provider-includes'])
			: [];
	}

	/**
	 * @param string $url
	 * @param array[] $input
	 * @return string[]
	 */
	private static function mapProviders(string $url, array $input): array
	{
		$output = [];
		$host = self::getHost($url);
		foreach (array_keys($input) as $url_pattern) {
			$path = str_replace('%hash%', $input[$url_pattern]['sha256'], $url_pattern);

			$separator = $url[-1] == '/' ? '' : '/';

			$output[] = $path[0] == '/'
				? "{$host}{$path}"
				: "{$url}{$separator}{$path}";
		}

		rsort($output);
		return $output;
	}

	private static function getHost(string $url): string
	{
		$url_info = parse_url($url);
		return "{$url_info['scheme']}://{$url_info['host']}";
	}

	public function getProvidersURL(string $package, string $hash): ?string
	{
		if ($this->providers_pattern == null) {
			return null;
		}

		$path = str_replace("%hash%", $hash,
			str_replace("%package%", $package, $this->providers_pattern)
		);
		$host = $this->getHost($this->url);
		return "{$host}{$path}";
	}

	public function getMetadataURL(string $package): string
	{
		$url_info = parse_url($this->url);
		$path = $this->metadata_pattern != null
			? str_replace("%package%", $package, $this->metadata_pattern)
			: "/packages/{$package}.json";
		return "{$url_info['scheme']}://{$url_info['host']}$path";
	}
}
