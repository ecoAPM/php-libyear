<?php

namespace ecoAPM\LibYear\Tests;

use cli\Progress;
use DateTimeImmutable;
use ecoAPM\LibYear\Calculator;
use ecoAPM\LibYear\ComposerFile;
use ecoAPM\LibYear\Dependency;
use ecoAPM\LibYear\Repository;
use ecoAPM\LibYear\RepositoryAPI;
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
		$dependency = new Dependency('vendor_name/package_name', '1.2.3');
		$composer = Mockery::mock(ComposerFile::class, [
			'getRepositories' => [$repository->url],
			'getDependencies' => [$dependency],
			'getMinimumStability' => 'stable',
		]);

		$api = Mockery::mock(RepositoryAPI::class, [
			'getInfo' => $repository,
			'getPackageInfo' => [
				['version' => '1.2.3', 'time' => '2018-07-01'],
				['version' => '2.3.4', 'extra' => ['drupal' => ['datestamp' => '1577836800']]]
			]
		]);
		$progress = Mockery::mock(Progress::class, [
			'setTotal' => null,
			'display' => null,
			'tick' => null,
			'finish' => null
		]);
		$calculator = new Calculator($composer, $api, $progress);

		//act
		$dependencies = $calculator->getDependencyInfo('.', false);

		//assert
		$this->assertEquals('1.2.3', $dependencies[0]->current_version->version_number);
		$this->assertEquals(new DateTimeImmutable('2018-07-01'), $dependencies[0]->current_version->released);
		$this->assertEquals('2.3.4', $dependencies[0]->newest_version->version_number);
		$this->assertEquals(new DateTimeImmutable('2020-01-01'), $dependencies[0]->newest_version->released);
	}

	public function testSkipsFillingOutMissingInfo()
	{
		//arrange
		$dependency1 = new Dependency('vendor1/package1', '1.2.3');
		$dependency2 = new Dependency('vendor1/package2', '2.3.4');
		$composer = Mockery::mock(ComposerFile::class, [
			'getRepositories' => [],
			'getDependencies' => [$dependency1, $dependency2],
			'getMinimumStability' => 'stable',
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

		$progress = Mockery::mock(Progress::class, [
			'setTotal' => null,
			'display' => null,
			'tick' => null,
			'finish' => null
		]);
		$calculator = new Calculator($composer, $api, $progress);

		//act
		$dependencies = $calculator->getDependencyInfo('.', false);

		//assert
		$this->assertEquals('1.2.3', $dependencies[0]->current_version->version_number);
		$this->assertNull($dependencies[0]->current_version->released);
		$this->assertEquals('2.3.4', $dependencies[1]->current_version->version_number);
		$this->assertNull($dependencies[1]->current_version->released);
	}

	public function testSkipsBadRepositories()
	{
		//arrange
		$dependency = new Dependency('vendor1/package1', '1.2.3');
		$composer = Mockery::mock(ComposerFile::class, [
			'getRepositories' => ['repo1', 'repo2'],
			'getDependencies' => [$dependency],
			'getMinimumStability' => 'stable',
		]);

		$api = Mockery::mock(RepositoryAPI::class, [
			'getPackageInfo' => [
				['version' => '1.2.4', 'time' => '2018-07-01']
			]
		]);
		$repo1 = null;
		$repo2 = new Repository('', null);
		$api->shouldReceive('getInfo')->andReturn(
			$repo1,
			$repo2
		);

		$progress = Mockery::mock(Progress::class, [
			'setTotal' => null,
			'display' => null,
			'tick' => null,
			'finish' => null
		]);
		$calculator = new Calculator($composer, $api, $progress);

		//act
		$calculator->getDependencyInfo('.', false);

		//assert
		$api->shouldNotHaveReceived('getPackageInfo', ['vendor1/package1', $repo1, false]);
		$api->shouldHaveReceived('getPackageInfo', ['vendor1/package1', $repo2, false]);
	}

	public function testSkipsFillingOutMissingVersions()
	{
		//arrange
		$dependency = new Dependency('vendor1/package1', '1.2.3');
		$composer = Mockery::mock(ComposerFile::class, [
			'getRepositories' => [],
			'getDependencies' => [$dependency],
			'getMinimumStability' => 'stable',
		]);

		$api = Mockery::mock(RepositoryAPI::class, [
				'getPackageInfo' => []
			]
		);

		$progress = Mockery::mock(Progress::class, [
			'setTotal' => null,
			'display' => null,
			'tick' => null,
			'finish' => null
		]);
		$calculator = new Calculator($composer, $api, $progress);

		//act
		$dependencies = $calculator->getDependencyInfo('.', false);

		//assert
		$this->assertEquals('1.2.3', $dependencies[0]->current_version->version_number);
		$this->assertNull($dependencies[0]->current_version->released);
	}

	public function testInfoInFirstRepoSkipsSubsequentOnes()
	{
		//arrange
		$dependency = new Dependency('vendor1/package1', '1.2.3');
		$composer = Mockery::mock(ComposerFile::class, [
			'getRepositories' => ['repo1', 'repo2'],
			'getDependencies' => [$dependency],
			'getMinimumStability' => 'stable',
		]);

		$repo1 = Mockery::mock(Repository::class);
		$repo2 = Mockery::mock(Repository::class);

		$api = Mockery::mock(RepositoryAPI::class);
		$api->shouldReceive('getInfo')->andReturn($repo1, $repo2);
		$api->shouldReceive('getPackageInfo')->with($dependency->name, $repo1, false)->andReturn([
			['version' => '1.2.4', 'time' => '2018-07-01']
		]);
		$api->shouldNotReceive('getPackageInfo')->with($dependency->name, $repo2);

		$progress = Mockery::mock(Progress::class, [
			'setTotal' => null,
			'display' => null,
			'tick' => null,
			'finish' => null
		]);
		$calculator = new Calculator($composer, $api, $progress);

		//act
		$results = $calculator->getDependencyInfo('.', false);

		//assert
		$this->assertEquals('1.2.4', $results[0]->newest_version->version_number);
	}

	public function testInfoNotInFirstRepoUsesSubsequentOnes()
	{
//arrange
		$dependency = new Dependency('vendor1/package1', '1.2.3');
		$composer = Mockery::mock(ComposerFile::class, [
			'getRepositories' => ['repo1', 'repo2'],
			'getDependencies' => [$dependency],
			'getMinimumStability' => 'stable',
		]);

		$repo1 = Mockery::mock(Repository::class);
		$repo2 = Mockery::mock(Repository::class);

		$api = Mockery::mock(RepositoryAPI::class);
		$api->shouldReceive('getInfo')->andReturn($repo1, $repo2);
		$api->shouldReceive('getPackageInfo')->with($dependency->name, $repo1, false)->andReturn([]);
		$api->shouldReceive('getPackageInfo')->with($dependency->name, $repo2, false)->andReturn([
			['version' => '1.2.4', 'time' => '2018-07-01']
		]);

		$progress = Mockery::mock(Progress::class, [
			'setTotal' => null,
			'display' => null,
			'tick' => null,
			'finish' => null
		]);
		$calculator = new Calculator($composer, $api, $progress);

		//act
		$results = $calculator->getDependencyInfo('.', false);

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

	public function testSkipBadStability()
	{
		//arrange
		$repository = new Repository('https://repo.packagist.org', '/p2/%package%.json');
		$dependency = new Dependency('vendor_name/package_name', '1.2.3');
		$composer = Mockery::mock(ComposerFile::class, [
			'getRepositories' => [$repository->url],
			'getDependencies' => [$dependency],
			'getMinimumStability' => 'stable',
		]);

		$api = Mockery::mock(RepositoryAPI::class, [
			'getInfo' => $repository,
			'getPackageInfo' => [
				['version' => '1.2.3', 'time' => '2018-07-01'],
				['version' => '2.3.4', 'time' => '2019-08-01'],
				['version' => '2.3.5-beta', 'time' => '2020-01-01'],
			]
		]);
		$progress = Mockery::mock(Progress::class, [
			'setTotal' => null,
			'display' => null,
			'tick' => null,
			'finish' => null
		]);
		$calculator = new Calculator($composer, $api, $progress);

		//act
		$dependencies = $calculator->getDependencyInfo('.', false);

		//assert
		$this->assertEquals('1.2.3', $dependencies[0]->current_version->version_number);
		$this->assertEquals(new DateTimeImmutable('2018-07-01'), $dependencies[0]->current_version->released);
		$this->assertEquals('2.3.4', $dependencies[0]->newest_version->version_number);
		$this->assertEquals(new DateTimeImmutable('2019-08-01'), $dependencies[0]->newest_version->released);
	}

	public function testKeepDevStabilityIfApplicable()
	{
		//arrange
		$repository = new Repository('https://repo.packagist.org', '/p2/%package%.json');
		$dependency = new Dependency('vendor_name/package_name', '1.2.3');
		$composer = Mockery::mock(ComposerFile::class, [
			'getRepositories' => [$repository->url],
			'getDependencies' => [$dependency],
			'getMinimumStability' => 'dev',
		]);

		$api = Mockery::mock(RepositoryAPI::class, [
			'getInfo' => $repository,
			'getPackageInfo' => [
				['version' => '1.2.3', 'time' => '2018-07-01'],
				['version' => '2.3.4', 'time' => '2019-08-01'],
				['version' => '2.3.5-beta', 'time' => '2020-01-01'],
			]
		]);
		$progress = Mockery::mock(Progress::class, [
			'setTotal' => null,
			'display' => null,
			'tick' => null,
			'finish' => null
		]);
		$calculator = new Calculator($composer, $api, $progress);

		//act
		$dependencies = $calculator->getDependencyInfo('.', false);

		//assert
		$this->assertEquals('1.2.3', $dependencies[0]->current_version->version_number);
		$this->assertEquals(new DateTimeImmutable('2018-07-01'), $dependencies[0]->current_version->released);
		$this->assertEquals('2.3.5-beta', $dependencies[0]->newest_version->version_number);
		$this->assertEquals(new DateTimeImmutable('2020-01-01'), $dependencies[0]->newest_version->released);
	}
}
