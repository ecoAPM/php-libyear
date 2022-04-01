<?php

namespace LibYear\Tests;

use GuzzleHttp\ClientInterface;
use LibYear\PackageAPI;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class PackagistAPITest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCanGetPackageInfo()
    {
        //arrange
        $http_client = Mockery::mock(ClientInterface::class, [
            'request' => Mockery::mock(ResponseInterface::class, [
				'getStatusCode' => 200,
				'getBody' => Mockery::mock(StreamInterface::class, [
                    'getContents' => json_encode([
						'packages' => [
							'vendor_name/package_name' => [
								['version' => '1.0.0', 'test_field' => 'test value']
							]
						],
					])
                ])
            ])
        ]);
        $api = new PackageAPI($http_client, STDERR);

        //act
        $package_info = $api->getPackageInfo('vendor_name/package_name', 'https://repo.packagist.org/p2/vendor_name/package_name.json');
        //assert
		$http_client->shouldHaveReceived('request')->with('GET', 'https://repo.packagist.org/p2/vendor_name/package_name.json');
        $this->assertEquals('test value', $package_info['1.0.0']['test_field']);
    }

	public function testCanHandleBadResponse()
	{
		//arrange
		$http_client = Mockery::mock(ClientInterface::class, [
			'request' => Mockery::mock(ResponseInterface::class, [
				'getStatusCode' => 200,
				'getBody' => Mockery::mock(StreamInterface::class, [
					'getContents' => '<html>This is not valid JSON</html>'
				])
			])
		]);
		$api = new PackageAPI($http_client, STDERR);

		//act
		$package_info = $api->getPackageInfo('vendor_name/package_name', 'https://repo.packagist.org/p2/vendor_name/package_name.json');
		$repository_info = $api->getRepositoryInfo('https://repo.packagist.org');

		//assert
		$this->assertEquals([], $package_info);
		$this->assertNull($repository_info);
	}

	public function testCanGetRepositoryInfo()
	{
		//arrange
		$http_client = Mockery::mock(ClientInterface::class, [
			'request' => Mockery::mock(ResponseInterface::class, [
				'getStatusCode' => 200,
				'getBody' => Mockery::mock(StreamInterface::class, [
					'getContents' => json_encode([
						'metadata-url' => '/p2/%package%.json',
						'available-packages' => ['vendor_name/package_name'],
					])
				])
			])
		]);
		$api = new PackageAPI($http_client, STDERR);

		//act
		$repository = $api->getRepositoryInfo('https://repo.packagist.org');

		//assert
		$http_client->shouldHaveReceived('request')->with('GET', 'https://repo.packagist.org/packages.json');
		$this->assertTrue($repository->hasPackage('vendor_name/package_name'));
		$this->assertFalse($repository->hasPackage('vendor_name/other_package_name'));
		$this->assertEquals(
			'https://repo.packagist.org/p2/vendor_name/package_name.json',
			$repository->getPackageUrl('vendor_name/package_name')
		);
	}
}
