<?php

namespace ecoAPM\LibYear\Tests;

use ecoAPM\LibYear\ComposerFile;
use ecoAPM\LibYear\Dependency;
use ecoAPM\LibYear\FileSystem;
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
			'exists' => true,
			'getJSON' => []
		]);
		$output = fopen('php://memory', 'a+');
		$composer = new ComposerFile($file_system, $output);

		//act
		$repositories = $composer->getRepositories('.');

		//assert
		$this->assertEquals(['https://repo.packagist.org'], $repositories);
	}

	public function testCachesFileSystemResponses()
	{
		//arrange
		$file_system = Mockery::mock(FileSystem::class, [
			'exists' => true,
			'getJSON' => []
		]);
		$output = fopen('php://memory', 'a+');
		$composer = new ComposerFile($file_system, $output);

		//act
		$composer->getRepositories('.');
		$composer->getRepositories('.');

		//assert
		$file_system->shouldHaveReceived('getJSON')->once();
	}

	public function testDisplaysErrorWhenFileNotFound()
	{
		//arrange
		$file_system = Mockery::mock(FileSystem::class, [
			'exists' => false
		]);
		$output = fopen('php://memory', 'a+');
		$composer = new ComposerFile($file_system, $output);

		//act
		$composer->getRepositories('.');

		//assert
		fseek($output, 0);
		$console = stream_get_contents($output);
		$this->assertNotEmpty($console);
	}

	public function testCanGetCustomRepositoryFromComposerFile()
	{
		//arrange
		$file_system = Mockery::mock(FileSystem::class, [
			'exists' => true,
			'getJSON' => [
				'repositories' => [
					['url' => 'https://composer.example.com'],
					['url' => 'https://composer.example.org']
				]
			]
		]);
		$output = fopen('php://memory', 'a+');
		$composer = new ComposerFile($file_system, $output);

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
			'exists' => true,
			'getJSON' => [
				'repositories' => [
					['url' => 'https://composer.example.com'],
					'packagist.org' => false
				]
			]
		]);
		$output = fopen('php://memory', 'a+');
		$composer = new ComposerFile($file_system, $output);

		//act
		$repositories = $composer->getRepositories('.');

		//assert
		$this->assertEquals(['https://composer.example.com'], $repositories);
	}

	public function testCanGetDependenciesFromComposerFiles()
	{
		//arrange
		$file_system = Mockery::mock(FileSystem::class, [
			'exists' => true,
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
		$output = fopen('php://memory', 'a+');
		$composer = new ComposerFile($file_system, $output);

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
			'exists' => true,
			'getJSON' => [
				'require' => [
					'vendor1/package1' => '1.2.3',
					'vendor1/package2' => '2.3.4',
					'vendor2/package1' => '3.4.5',
				]
			]
		]);
		$output = fopen('php://memory', 'a+');
		$composer = new ComposerFile($file_system, $output);

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
			'exists' => true,
			'getJSON' => [
				'require-dev' => [
					'vendor3/package1' => '4.5.6'
				]
			]
		]);
		$output = fopen('php://memory', 'a+');
		$composer = new ComposerFile($file_system, $output);

		//act
		$dependencies = $composer->getDependencies('.');

		//assert
		$this->assertEquals('4.5.6', $dependencies['vendor3/package1']->current_version->version_number);
	}

	public function testWillPullVersionFromLockFileWhenAvailable()
	{
		//arrange
		$file_system = Mockery::mock(FileSystem::class, [
			'exists' => true
		]);
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
		$output = fopen('php://memory', 'a+');
		$composer = new ComposerFile($file_system, $output);

		//act
		$dependencies = $composer->getDependencies('.');

		//assert
		$this->assertEquals('1.2.3', $dependencies['vendor_name/package_name']->current_version->version_number);
	}

	public function testCanUpdateFile()
	{
		//arrange
		$file_system = Mockery::mock(FileSystem::class, [
			'exists' => true,
			'getJSON' => [
				'require' => [
					'dep1' => '1.2.3',
					'dep2' => '2.3.4'
				],
				'require-dev' => [
					'dep3' => '3.4.5',
					'dep4' => '4.5.6'
				]
			],
			'saveJSON' => null
		]);
		$output = fopen('php://memory', 'a+');
		$composer = new ComposerFile($file_system, $output);

		$dep1 = new Dependency('dep1', '1.2.3');
		$dep1->newest_version->version_number = '1.2.3';
		$dep2 = new Dependency('dep2', '2.3.4');
		$dep2->newest_version->version_number = '2.3.5';
		$dep3 = new Dependency('dep3', '3.4.5');
		$dep3->newest_version->version_number = '3.4.6';
		$dep4 = new Dependency('dep4', '4.5.6');
		$dependencies = [$dep1, $dep2, $dep3, $dep4];

		//act
		$composer->update('.', $dependencies);

		//assert
		$file_system->shouldHaveReceived('saveJSON')->with('.' . DIRECTORY_SEPARATOR . 'composer.json', [
			'require' => [
				'dep1' => '1.2.3',
				'dep2' => '2.3.5'
			],
			'require-dev' => [
				'dep3' => '3.4.6',
				'dep4' => '4.5.6'
			]
		]);
	}

	public function testCanGetMinimumStability()
	{
		//arrange
		$file_system = Mockery::mock(FileSystem::class, [
			'exists' => true,
			'getJSON' => [
				'minimum-stability' => 'dev'
			]
		]);
		$output = fopen('php://memory', 'a+');
		$composer = new ComposerFile($file_system, $output);

		//act
		$repositories = $composer->getMinimumStability('.');

		$this->assertEquals('dev', $repositories);
	}

	public function testMinimumStabilityShouldBeStableByDefault()
	{
		//arrange
		$file_system = Mockery::mock(FileSystem::class, [
			'exists' => true,
			'getJSON' => [
			]
		]);
		$output = fopen('php://memory', 'a+');
		$composer = new ComposerFile($file_system, $output);

		//act
		$repositories = $composer->getMinimumStability('.');

		$this->assertEquals('stable', $repositories);
	}
}
