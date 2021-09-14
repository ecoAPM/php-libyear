<?php

namespace LibYear\Tests;

use DateTime;
use LibYear\Calculator;
use LibYear\ComposerFile;
use LibYear\Dependency;
use LibYear\PackagistAPI;
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
            'getDependencies' => [$dependency]
        ]);

        $packagist = Mockery::mock(PackagistAPI::class, [
            'getPackageInfo' => [
                'package' => [
                    'versions' => [
                        '1.2.3' => ['time' => '2018-07-01'],
                        '2.3.4' => ['time' => '2020-01-01']
                    ]
                ]
            ]
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
            'getDependencies' => [$dependency1, $dependency2]
        ]);

        $packagist = Mockery::mock(PackagistAPI::class);
        $packagist->shouldReceive('getPackageInfo')->andReturn(
            [
                'package' => [
                    'versions' => [
                        '1.2.4' => ['time' => '2018-07-01']
                    ]
                ]
            ],
            []
        );
        $calculator = new Calculator($composer, $packagist);

        //act
        $dependencies = $calculator->getDependencyInfo('.');

        //assert
        $this->assertEquals('1.2.3', $dependencies[0]->current_version->version_number);
        $this->assertNull($dependencies[0]->current_version->released);
        $this->assertEquals('2.3.4', $dependencies[1]->current_version->version_number);
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
