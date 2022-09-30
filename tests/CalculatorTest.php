<?php

namespace LibYear\Tests;

use DateTimeImmutable;
use LibYear\Calculator;
use LibYear\ComposerFile;
use LibYear\Dependency;
use LibYear\Repository;
use LibYear\RepositoryAPI;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CalculatorTest extends TestCase
{
	use MockeryPHPUnitIntegration;

	public function testCanFillOutDependencyInfo()
	{
		//arrange
		$repository = new Repository('https://repo.packagist.org', '/p2/%package%.json');
		$dependency = new Dependency();
		$dependency->name = 'vendor_name/package_name';
		$dependency->current_version->version_number = '1.2.3';
		$composer = Mockery::mock(ComposerFile::class, [
			'getRepositories' => [$repository->url],
			'getDependencies' => [$dependency]
		]);

		$api = Mockery::mock(RepositoryAPI::class, [
			'getInfo' => $repository,
			'getPackageInfo' => [
				['version' => '1.2.3', 'time' => '2018-07-01'],
				['version' => '2.3.4', 'extra' => ['drupal' => ['datestamp' => '1577836800']]]
			]
		]);
		$calculator = new Calculator($composer, $api);

		//act
		$dependencies = $calculator->getDependencyInfo('.');

		//assert
		$this->assertEquals('1.2.3', $dependencies[0]->current_version->version_number);
		$this->assertEquals(new DateTimeImmutable('2018-07-01'), $dependencies[0]->current_version->released);
		$this->assertEquals('2.3.4', $dependencies[0]->newest_version->version_number);
		$this->assertEquals(new DateTimeImmutable('2020-01-01'), $dependencies[0]->newest_version->released);
	}

	public function testSkipsFillingOutMissingInfo()
	{
		//arrange
		$dependency1 = new Dependency();
		$dependency1->name = 'vendor1/package1';
		$dependency1->current_version->version_number = '1.2.3';
		$dependency2 = new Dependency();
		$dependency2->name = 'vendor1/package2';
		$dependency2->current_version->version_number = '2.3.4';
		$composer = Mockery::mock(ComposerFile::class, [
			'getRepositories' => [],
			'getDependencies' => [$dependency1, $dependency2]
		]);

		$api = Mockery::mock(RepositoryAPI::class);
		$api->shouldReceive('getPackageInfo')->andReturn(
			[
				['version' => '1.2.4', 'time' => '2018-07-01']
			],
			[
				[]
			]
		);
		$calculator = new Calculator($composer, $api);

		//act
		$dependencies = $calculator->getDependencyInfo('.');

		//assert
		$this->assertEquals('1.2.3', $dependencies[0]->current_version->version_number);
		$this->assertNull($dependencies[0]->current_version->released);
		$this->assertEquals('2.3.4', $dependencies[1]->current_version->version_number);
		$this->assertNull($dependencies[1]->current_version->released);
	}

	public function testSkipsFillingOutMissingVersions()
	{
		//arrange
		$dependency = new Dependency();
		$dependency->name = 'vendor1/package1';
		$dependency->current_version->version_number = '1.2.3';
		$composer = Mockery::mock(ComposerFile::class, [
			'getRepositories' => [],
			'getDependencies' => [$dependency]
		]);

		$api = Mockery::mock(RepositoryAPI::class, [
				'getPackageInfo' => []
			]
		);
		$calculator = new Calculator($composer, $api);

		//act
		$dependencies = $calculator->getDependencyInfo('.');

		//assert
		$this->assertEquals('1.2.3', $dependencies[0]->current_version->version_number);
		$this->assertNull($dependencies[0]->current_version->released);
	}

	public function testInfoInFirstRepoSkipsSubsequentOnes()
	{
		//arrange
		$dependency = new Dependency();
		$dependency->name = 'vendor1/package1';
		$dependency->current_version->version_number = '1.2.3';
		$composer = Mockery::mock(ComposerFile::class, [
			'getRepositories' => ['repo1', 'repo2'],
			'getDependencies' => [$dependency]
		]);

		$repo1 = Mockery::mock(Repository::class);
		$repo2 = Mockery::mock(Repository::class);

		$api = Mockery::mock(RepositoryAPI::class);
		$api->shouldReceive('getInfo')->andReturn($repo1, $repo2);
		$api->shouldReceive('getPackageInfo')->with($dependency->name, $repo1)->andReturn([
			['version' => '1.2.4', 'time' => '2018-07-01']
		]);
		$api->shouldNotReceive('getPackageInfo')->with($dependency->name, $repo2);

		$calculator = new Calculator($composer, $api);

		//act
		$results = $calculator->getDependencyInfo('.');

		//assert
		$this->assertEquals('1.2.4', $results[0]->newest_version->version_number);
	}

	public function testInfoNotInFirstRepoUsesSubsequentOnes()
	{
//arrange
		$dependency = new Dependency();
		$dependency->name = 'vendor1/package1';
		$dependency->current_version->version_number = '1.2.3';
		$composer = Mockery::mock(ComposerFile::class, [
			'getRepositories' => ['repo1', 'repo2'],
			'getDependencies' => [$dependency]
		]);

		$repo1 = Mockery::mock(Repository::class);
		$repo2 = Mockery::mock(Repository::class);

		$api = Mockery::mock(RepositoryAPI::class);
		$api->shouldReceive('getInfo')->andReturn($repo1, $repo2);
		$api->shouldReceive('getPackageInfo')->with($dependency->name, $repo1)->andReturn([]);
		$api->shouldReceive('getPackageInfo')->with($dependency->name, $repo2)->andReturn([
			['version' => '1.2.4', 'time' => '2018-07-01']
		]);

		$calculator = new Calculator($composer, $api);

		//act
		$results = $calculator->getDependencyInfo('.');

		//assert
		$this->assertEquals('1.2.4', $results[0]->newest_version->version_number);
	}

	public function testCanGetTotalLibyearsBehind()
	{
		//arrange
		$dependencies = [
			Mockery::mock(Dependency::class, [
				'getLibyearsBehind' => 1.25
			]),
			Mockery::mock(Dependency::class, [
				'getLibyearsBehind' => 2.5
			])
		];

		//act
		$total_behind = Calculator::getTotalLibyearsBehind($dependencies);

		//assert
		$this->assertEquals(3.75, $total_behind);
	}
}
