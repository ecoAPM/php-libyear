<?php

namespace LibYear\Tests;

use DateTime;
use LibYear\Calculator;
use LibYear\ComposerFile;
use LibYear\Dependency;
use LibYear\PackageAPI;
use LibYear\Repository;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CalculatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCanFillOutDependencyInfo()
    {
        //arrange
        $dependency = new Dependency();
        $dependency->name = 'vendor_name/package_name';
        $dependency->current_version->version_number = '1.2.3';
        $composer = Mockery::mock(ComposerFile::class, [
            'getDependencies' => [$dependency],
			'getRepositoriesUrl' => []
        ]);

        $packagist = Mockery::mock(PackageAPI::class, [
            'getPackageInfo' => [
				'1.2.3' => ['time' => '2018-07-01'],
				'2.3.4' => ['time' => '2020-01-01']
            ],
			'getRepositoryInfo' => new Repository('repo.packagist.org', '/p2/%package%.json', [])
        ]);
        $calculator = new Calculator($composer, $packagist);

        //act
        $dependencies = $calculator->getDependencyInfo('.');

        //assert
        $this->assertEquals('1.2.3', $dependencies[0]->current_version->version_number);
        $this->assertEquals(new DateTime('2018-07-01'), $dependencies[0]->current_version->released);
        $this->assertEquals('2.3.4', $dependencies[0]->newest_version->version_number);
        $this->assertEquals(new DateTime('2020-01-01'), $dependencies[0]->newest_version->released);
    }

	public function testCanFillOutDependencyInfoWithMultipleRepositories()
	{
		//arrange
		$dependency1 = new Dependency();
		$dependency1->name = 'vendor_name/package_name';
		$dependency1->current_version->version_number = '1.2.3';

		$dependency2 = new Dependency();
		$dependency2->name = 'vendor_name/second_package_name';
		$dependency2->current_version->version_number = '5.6.7';

		$composer = Mockery::mock(ComposerFile::class, [
			'getDependencies' => [$dependency1, $dependency2],
			'getRepositoriesUrl' => ['https://custom-repo.com', 'https://repo.packagist.org']
		]);

		$packagist = Mockery::mock(PackageAPI::class);

		$packagist->shouldReceive('getRepositoryInfo')
			->with('https://custom-repo.com')
			->andReturn(new Repository('https://custom-repo.com', '/p2/%package%.json', ['vendor_name/second_package_name']));
		$packagist->shouldReceive('getRepositoryInfo')
			->with('https://repo.packagist.org')
			->andReturn(new Repository('https://repo.packagist.org', '/p2/%package%.json', []));

		$packagist->shouldReceive('getPackageInfo')
			->with('vendor_name/package_name', 'https://repo.packagist.org/p2/vendor_name/package_name.json')
			->andReturn([
				'1.2.3' => ['time' => '2018-07-01'],
				'2.3.4' => ['time' => '2020-01-01']
			]);

		$packagist->shouldReceive('getPackageInfo')
			->with('vendor_name/second_package_name', 'https://custom-repo.com/p2/vendor_name/second_package_name.json')
			->andReturn([
				'3.4.5' => ['time' => '2020-08-01'],
				'5.6.7' => ['time' => '2022-01-01']
			]);
		$calculator = new Calculator($composer, $packagist);

		//act
		$dependencies = $calculator->getDependencyInfo('.');

		//assert
		$this->assertEquals('1.2.3', $dependencies[0]->current_version->version_number);
		$this->assertEquals(new DateTime('2018-07-01'), $dependencies[0]->current_version->released);
		$this->assertEquals('2.3.4', $dependencies[0]->newest_version->version_number);
		$this->assertEquals(new DateTime('2020-01-01'), $dependencies[0]->newest_version->released);

		$this->assertEquals('5.6.7', $dependencies[1]->current_version->version_number);
		$this->assertEquals(new DateTime('2022-01-01'), $dependencies[1]->current_version->released);
		$this->assertEquals('5.6.7', $dependencies[1]->newest_version->version_number);
		$this->assertEquals(new DateTime('2022-01-01'), $dependencies[1]->newest_version->released);
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
            'getDependencies' => [$dependency1, $dependency2],
			'getRepositoriesUrl' => []
        ]);

        $packagist = Mockery::mock(PackageAPI::class);
        $packagist->shouldReceive('getPackageInfo')->andReturn(
            [
				'1.2.4' => ['time' => '2018-07-01']
            ],
            []
        );
		$packagist->shouldReceive('getRepositoryInfo')->andReturn(
			new Repository('repo.packagist.org', '/p2/%package%.json', [])
		);
        $calculator = new Calculator($composer, $packagist);

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
            'getDependencies' => [$dependency],
			'getRepositoriesUrl' => []
        ]);

        $packagist = Mockery::mock(PackageAPI::class);
        $packagist->shouldReceive('getPackageInfo')->andReturn([]);
		$packagist->shouldReceive('getRepositoryInfo')->andReturn(
			new Repository('repo.packagist.org', '/p2/%package%.json', [])
		);
        $calculator = new Calculator($composer, $packagist);

        //act
        $dependencies = $calculator->getDependencyInfo('.');

        //assert
        $this->assertEquals('1.2.3', $dependencies[0]->current_version->version_number);
        $this->assertNull($dependencies[0]->current_version->released);
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
