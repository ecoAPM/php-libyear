<?php

namespace LibYear\Tests;

use DateTimeImmutable;
use LibYear\Dependency;
use PHPUnit\Framework\TestCase;

class DependencyTest extends TestCase
{
	public function testCanGetTotalLibyearsBehind()
	{
		//arrange
		$dependency = new Dependency('test', '1.2.3');
		$dependency->current_version->released = new DateTimeImmutable('-18 months');
		$dependency->newest_version->released = new DateTimeImmutable('today');

		//act
		$libyears = $dependency->getLibyearsBehind();

		//assert
		$this->assertEqualsWithDelta(1.5, $libyears, 0.01);
	}

	public function testLibyearsIsZeroWhenOnNewestVersion()
	{
		//arrange
		$dependency = new Dependency('test', '1.2.3');
		$dependency->current_version->released = new DateTimeImmutable('-18 months');
		$dependency->newest_version->released = new DateTimeImmutable('-18 months');

		//act
		$libyears = $dependency->getLibyearsBehind();

		//assert
		$this->assertEquals(0, $libyears);
	}

	public function testCanGetNoNewestVersion()
	{
		//arrange
		$dependency = new Dependency('test', '1.2.3');
		$dependency->current_version->released = new DateTimeImmutable('-18 months');

		//act
		$libyears = $dependency->getLibyearsBehind();

		//assert
		$this->assertNull($libyears);
	}
}
