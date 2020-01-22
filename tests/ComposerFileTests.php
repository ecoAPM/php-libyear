<?php

namespace Libyear\Tests;

use Libyear\ComposerFile;
use Libyear\FileSystem;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ComposerFileTests extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCanGetDependenciesFromComposerFiles()
    {
        //arrange
        $file_system = Mockery::mock(FileSystem::class, [
            'getJSON' => [
                'require' => [
                    'vendor1/package1' => '1.2.3',
                    'vendor1/package2' => '2.3.4',
                    'vendor2/package1' => '3.4.5',
                ],
                'require-dev' => [
                    'vendor3/package1' => '4.5.6'
                ]
            ]
        ]);
        $composer = new ComposerFile($file_system);

        //act
        $dependencies = $composer->getDependencies('.');

        //assert
        $this->assertEquals('1.2.3', $dependencies['vendor1/package1']->current_version->version_number);
        $this->assertEquals('2.3.4', $dependencies['vendor1/package2']->current_version->version_number);
        $this->assertEquals('3.4.5', $dependencies['vendor2/package1']->current_version->version_number);
        $this->assertEquals('4.5.6', $dependencies['vendor3/package1']->current_version->version_number);
    }

    public function testCanGetDependenciesWhenNoDev()
    {
        //arrange
        $file_system = Mockery::mock(FileSystem::class, [
            'getJSON' => [
                'require' => [
                    'vendor1/package1' => '1.2.3',
                    'vendor1/package2' => '2.3.4',
                    'vendor2/package1' => '3.4.5',
                ]
            ]
        ]);
        $composer = new ComposerFile($file_system);

        //act
        $dependencies = $composer->getDependencies('.');

        //assert
        $this->assertEquals('1.2.3', $dependencies['vendor1/package1']->current_version->version_number);
        $this->assertEquals('2.3.4', $dependencies['vendor1/package2']->current_version->version_number);
        $this->assertEquals('3.4.5', $dependencies['vendor2/package1']->current_version->version_number);
    }

    public function testCanGetDependenciesWhenOnlyDev()
    {
        //arrange
        $file_system = Mockery::mock(FileSystem::class, [
            'getJSON' => [
                'require-dev' => [
                    'vendor3/package1' => '4.5.6'
                ]
            ]
        ]);
        $composer = new ComposerFile($file_system);

        //act
        $dependencies = $composer->getDependencies('.');

        //assert
        $this->assertEquals('4.5.6', $dependencies['vendor3/package1']->current_version->version_number);
    }

    public function testWillPullVersionFromLockFileWhenAvailable()
    {
        //arrange
        $file_system = Mockery::mock(FileSystem::class);
        $file_system->shouldReceive('getJSON')->andReturn([
            'require' => [
                'vendor_name/package_name' => '^1.2'
            ]
        ],
            [
                'packages' => [
                    [
                        'name' => 'vendor_name/package_name',
                        'version' => '1.2.3'
                    ]
                ]
            ]);
        $composer = new ComposerFile($file_system);

        //act
        $dependencies = $composer->getDependencies('.');

        //assert
        $this->assertEquals('1.2.3', $dependencies['vendor_name/package_name']->current_version->version_number);
    }
}