<?php

namespace LibYear\Tests;

use GuzzleHttp\ClientInterface;
use LibYear\Repository;
use LibYear\RepositoryAPI;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class RepositoryAPITest extends TestCase
{
	use MockeryPHPUnitIntegration;

	public function testCanGetRepositoryInfo()
	{
		//arrange
		$http_client = Mockery::mock(ClientInterface::class, [
			'request' => Mockery::mock(ResponseInterface::class, [
				'getStatusCode' => 200,
				'getBody' => Mockery::mock(StreamInterface::class, [
					'getContents' => json_encode(['metadata-url' => '/metadata/%package%.json'])
				])
			])
		]);
		$api = new RepositoryAPI($http_client, STDERR);

		//act
		$repo = $api->getInfo('https://composer.example.com');

		//assert
		$this->assertEquals('https://composer.example.com', $repo->url);
		$this->assertEquals('/metadata/%package%.json', $repo->metadata_pattern);
	}

	public function testGetPackageInfoCallsCorrectURL()
	{
		//arrange
		$repo = new Repository('https://repo.packagist.org', '/packages/%package%.json');
		$http_client = Mockery::mock(ClientInterface::class, [
			'request' => Mockery::mock(ResponseInterface::class, [
				'getStatusCode' => 200,
				'getBody' => Mockery::mock(StreamInterface::class, [
					'getContents' => json_encode([
						'packages' => [
							'vendor_name/package_name' => []
						]
					])
				])
			])
		]);
		$api = new RepositoryAPI($http_client, STDERR);

		//act
		$api->getPackageInfo('vendor_name/package_name', $repo);

		//assert
		$http_client->shouldHaveReceived('request')
			->with('GET', 'https://repo.packagist.org/packages/vendor_name/package_name.json');
	}

	public function testCanGetPackageInfo()
	{
		//arrange
		$repo = new Repository('https://repo.packagist.org', '/packages/%package%.json');
		$http_client = Mockery::mock(ClientInterface::class, [
			'request' => Mockery::mock(ResponseInterface::class, [
				'getStatusCode' => 200,
				'getBody' => Mockery::mock(StreamInterface::class, [
					'getContents' => json_encode([
						'packages' => [
							'vendor_name/package_name' => [
								'test_field' => 'test value'
							]
						]
					])
				])
			])
		]);
		$api = new RepositoryAPI($http_client, STDERR);

		//act
		$package_info = $api->getPackageInfo('vendor_name/package_name', $repo);

		//assert
		$this->assertEquals('test value', $package_info['test_field']);
	}

	public function testCanHandleBadResponse()
	{
		//arrange
		$repo = new Repository('https://repo.packagist.org', '/packages/%package%.json');
		$http_client = Mockery::mock(ClientInterface::class, [
			'request' => Mockery::mock(ResponseInterface::class, [
				'getStatusCode' => 200,
				'getBody' => Mockery::mock(StreamInterface::class, [
					'getContents' => '<html lang="en">This is not valid JSON</html>'
				])
			])
		]);
		$api = new RepositoryAPI($http_client, STDERR);

		//act
		$package_info = $api->getPackageInfo('vendor_name/package_name', $repo);

		//assert
		$this->assertEquals([], $package_info);
	}
}
