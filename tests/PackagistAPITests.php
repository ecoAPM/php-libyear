<?php

namespace LibYear\Tests;

use GuzzleHttp\ClientInterface;
use LibYear\PackagistAPI;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class PackagistAPITests extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCallsCorrectURL()
    {
        //arrange
        $http_client = Mockery::mock(ClientInterface::class, [
            'request' => Mockery::mock(ResponseInterface::class, [
                'getBody' => Mockery::mock(StreamInterface::class, [
                    'getContents' => json_encode(['test_field' => 'test value'])
                ])
            ])
        ]);
        $api = new PackagistAPI($http_client);

        //act
        $package_info = $api->getPackageInfo('vendor_name/package_name');

        //assert
        $http_client->shouldHaveReceived('request')->with('GET', 'https://repo.packagist.org/packages/vendor_name/package_name.json');
    }

    public function testCanGetPackageInfo()
    {
        //arrange
        $http_client = Mockery::mock(ClientInterface::class, [
            'request' => Mockery::mock(ResponseInterface::class, [
                'getBody' => Mockery::mock(StreamInterface::class, [
                    'getContents' => json_encode(['test_field' => 'test value'])
                ])
            ])
        ]);
        $api = new PackagistAPI($http_client);

        //act
        $package_info = $api->getPackageInfo('vendor_name/package_name');

        //assert
        $this->assertEquals('test value', $package_info['test_field']);
    }

    public function testCanHandleBadResponse()
    {
        //arrange
        $http_client = Mockery::mock(ClientInterface::class, [
            'request' => Mockery::mock(ResponseInterface::class, [
                'getBody' => Mockery::mock(StreamInterface::class, [
                    'getContents' => '<html>This is not valid JSON</html>'
                ])
            ])
        ]);
        $api = new PackagistAPI($http_client);

        //act
        $package_info = $api->getPackageInfo('vendor_name/package_name');

        //assert
        $this->assertEquals([], $package_info);
    }
}
