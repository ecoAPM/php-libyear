<?php

namespace LibYear\Tests;

use DateTimeImmutable;
use LibYear\App;
use LibYear\Calculator;
use LibYear\Dependency;
use LibYear\Version;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class AppTest extends TestCase
{
	/** @var Calculator|MockInterface */
	private Calculator $calculator;

	public function setUp(): void
	{
		$dep1 = new Dependency();
		$dep1->name = 'Test 1';
		$dep1->current_version = new Version();
		$dep1->current_version->version_number = '1.0.0';
		$dep1->current_version->released = new DateTimeImmutable('2020-01-01');
		$dep1->newest_version = new Version();
		$dep1->newest_version->version_number = '1.0.0';
		$dep1->newest_version->released = new DateTimeImmutable('2020-01-01');

		$dep2 = new Dependency();
		$dep2->name = 'Test 2';
		$dep2->current_version = new Version();
		$dep2->current_version->version_number = '1.0.0';
		$dep2->current_version->released = new DateTimeImmutable('2020-01-01');
		$dep2->newest_version = new Version();
		$dep2->newest_version->version_number = '2.0.0';
		$dep2->newest_version->released = new DateTimeImmutable('2021-01-01');

		$this->calculator = Mockery::mock(Calculator::class, [
			'getDependencyInfo' => [$dep1, $dep2]
		]);
	}

	public function testShowsAllDependenciesByDefaut()
	{
		//arrange
		$output = fopen('php://memory', 'a+');
		$app = new App($this->calculator, $output);

		//act
		$app->run(['libyear', '.']);

		//assert
		fseek($output, 0);
		$console = stream_get_contents($output);
		$this->assertStringContainsString('Test 1', $console);
		$this->assertStringContainsString('Test 2', $console);
	}

	public function testQuietModeOnlyShowsOutdated()
	{
		//arrange
		$output = fopen('php://memory', 'a+');
		$app = new App($this->calculator, $output);

		//act
		$app->run(['libyear', '.', '-q']);

		//assert
		fseek($output, 0);
		$console = stream_get_contents($output);
		$this->assertStringContainsString('Test 2', $console);
		$this->assertStringNotContainsString('Test 1', $console);
	}
}