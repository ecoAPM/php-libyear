<?php

namespace LibYear\Tests;

use LibYear\ComposerFile;
use LibYear\FileSystem;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ComposerFileTest extends TestCase
{
	use MockeryPHPUnitIntegration;

	public function testCanGetDefaultRepositoryFromComposerFile()
	{
		//arrange
		$file_system = Mockery::mock(FileSystem::class, [
			'getJSON' => []
		]);
		$composer = new ComposerFile($file_system);

		//act
		$repositories = $composer->getRepositories('.');

		//assert
		$this->assertEquals(['https://repo.packagist.org'], $repositories);
	}

	public function testCanGetCustomRepositoryFromComposerFile()
	{
		//arrange
		$file_system = Mockery::mock(FileSystem::class, [
			'getJSON' => [
				'repositories' => [
					['url' => 'https://composer.example.com'],
					['url' => 'https://composer.example.org']
				]
			]
		]);
		$composer = new ComposerFile($file_system);

		//act
		$repositories = $composer->getRepositories('.');

		//assert
		$expected = [
			'https://composer.example.com',
			'https://composer.example.org',
			'https://repo.packagist.org'
		];
		$this->assertEquals($expected, $repositories);
	}

	public function testCanSkipPackagist()
	{
		//arrange
		$file_system = Mockery::mock(FileSystem::class, [
			'getJSON' => [
				'repositories' => [
					['url' => 'https://composer.example.com'],
					'packagist.org' => false
				]
			]
		]);
		$composer = new ComposerFile($file_system);

		//act
		$repositories = $composer->getRepositories('.');

		//assert
		$this->assertEquals(['https://composer.example.com'], $repositories);
	}

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
		$file_system->shouldReceive('getJSON')->andReturn(
			[
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
			]
		);
		$composer = new ComposerFile($file_system);

		//act
		$dependencies = $composer->getDependencies('.');

		//assert
		$this->assertEquals('1.2.3', $dependencies['vendor_name/package_name']->current_version->version_number);
	}
}
