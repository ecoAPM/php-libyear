<?php

namespace ecoAPM\LibYear\Tests;

use ecoAPM\LibYear\Repository;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
	private static array $params = [
		'metadata-url' => '/metadata/%package%.json',
		'providers-url' => '/provider/%package%/%hash%.json'
	];

	public function providers()
	{
		return [
			['https://example.com/packages', 'providers-latest/%hash%.json', 'abc123', 'https://example.com/packages/providers-latest/abc123.json'],
			['https://example.com/packages/', 'providers-latest/%hash%.json', 'abc123', 'https://example.com/packages/providers-latest/abc123.json'],
			['https://example.com/packages', '/providers-latest/%hash%.json', 'abc123', 'https://example.com/providers-latest/abc123.json'],
			['https://example.com/packages/', '/providers-latest/%hash%.json', 'abc123', 'https://example.com/providers-latest/abc123.json']
		];
	}

	/** @dataProvider providers */
	public function testMapsProviders($base, $url, $hash, $expected)
	{
		//arrange
		$params = self::$params;
		$params['provider-includes'] = [$url => ['sha256' => $hash]];
		$repo = new Repository($base, $params);

		//act
		$providers = $repo->providers;

		//assert
		$this->assertEquals($expected, $providers[0]);

	}

	public function testCanGetPackageProviderURL()
	{
		//arrange
		$repo = new Repository("https://composer.example.com/packages", self::$params);

		//act
		$url = $repo->getProvidersURL("ecoapm/libyear", "abc123");

		//assert
		$this->assertEquals("https://composer.example.com/provider/ecoapm/libyear/abc123.json", $url);
	}

	public function testCanGetPackageMetadataURL()
	{
		//arrange
		$repo = new Repository("https://composer.example.com/packages", self::$params);

		//act
		$url = $repo->getMetadataURL("ecoapm/libyear");

		//assert
		$this->assertEquals("https://composer.example.com/metadata/ecoapm/libyear.json", $url);
	}

	public function testCanGetDefaultPackageMetadataURL()
	{
		//arrange
		$repo = new Repository("https://composer.example.com/packages", []);

		//act
		$url = $repo->getMetadataURL("ecoapm/libyear");

		//assert
		$this->assertEquals("https://composer.example.com/packages/ecoapm/libyear.json", $url);
	}
}
