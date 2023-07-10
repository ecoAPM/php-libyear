<?php

namespace ecoAPM\LibYear\Tests;

use DateTimeImmutable;
use ecoAPM\LibYear\App;
use ecoAPM\LibYear\Calculator;
use ecoAPM\LibYear\ComposerFile;
use ecoAPM\LibYear\Dependency;
use Garden\Cli\Args;
use Garden\Cli\Cli;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class AppTest extends TestCase
{
	use MockeryPHPUnitIntegration;

	private static function calculator(): Calculator
	{
		$dep1 = new Dependency('Test 1', '1.0.0');
		$dep1->current_version->released = new DateTimeImmutable('2020-01-01');
		$dep1->newest_version->version_number = '1.0.0';
		$dep1->newest_version->released = new DateTimeImmutable('2020-01-01');

		$dep2 = new Dependency('Test 2', '1.0.0');
		$dep2->current_version->released = new DateTimeImmutable('2020-01-01');
		$dep2->newest_version->version_number = '2.0.0';
		$dep2->newest_version->released = new DateTimeImmutable('2021-01-01');

		return Mockery::mock(Calculator::class, [
			'getDependencyInfo' => [$dep1, $dep2]
		]);
	}

	public function testCanDisplayHelpText()
	{
		//arrange
		$composer = Mockery::mock(ComposerFile::class);
		$output = fopen('php://memory', 'a+');
		$app = new App(new Cli(), self::calculator(), $composer, $output);

		//act
		$app->run(['libyear', '--help']);

		//assert
		fseek($output, 0);
		$console = stream_get_contents($output);
		$this->assertStringContainsString('OPTIONS', $console);
		$this->assertStringContainsString('ARGUMENTS', $console);
	}

	public function testShowsAllDependenciesByDefault()
	{
		//arrange
		$composer = Mockery::mock(ComposerFile::class);
		$output = fopen('php://memory', 'a+');
		$app = new App(new Cli(), self::calculator(), $composer, $output);

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
		$composer = Mockery::mock(ComposerFile::class);
		$output = fopen('php://memory', 'a+');
		$app = new App(new Cli(), self::calculator(), $composer, $output);

		//act
		$app->run(['libyear', '-q', 'test']);

		//assert
		fseek($output, 0);
		$console = stream_get_contents($output);
		$this->assertStringContainsString('Test 2', $console);
		$this->assertStringNotContainsString('Test 1', $console);
	}

	public function testUpdatesComposerIfFlagSet()
	{
		//arrange
		$composer = Mockery::mock(ComposerFile::class, [
			'update' => null
		]);
		$output = fopen('php://memory', 'a+');
		$app = new App(new Cli(), self::calculator(), $composer, $output);

		//act
		$app->run(['libyear', '.', '-u']);

		//assert
		$composer->shouldHaveReceived('update');
	}

	public function testDoesNotUpdateComposerIfFlagNotSet()
	{

		//arrange
		$composer = Mockery::mock(ComposerFile::class);
		$output = fopen('php://memory', 'a+');
		$app = new App(new Cli(), self::calculator(), $composer, $output);

		//act
		$app->run(['libyear', '.']);

		//assert
		$composer->shouldNotHaveReceived('update');
	}
}
