<?php

namespace Libyear\Tests;

use DateTime;
use Libyear\Dependency;
use PHPUnit\Framework\TestCase;

class DependencyTests extends TestCase
{
    public function testCanGetTotalLibyearsBehind()
    {
        //arrange
        $dependency = new Dependency();
        $dependency->current_version->released = new DateTime('-18 months');
        $dependency->newest_version->released = new DateTime('today');

        //act
        $libyears = $dependency->getLibyearsBehind();

        //assert
        $this->assertEqualsWithDelta(1.5, $libyears, 0.01);
    }

    public function testLibyearsIsZeroWhenOnNewestVersion()
    {
        //arrange
        $dependency = new Dependency();
        $dependency->current_version->released = new DateTime('-18 months');
        $dependency->newest_version->released = new DateTime('-18 months');

        //act
        $libyears = $dependency->getLibyearsBehind();

        //assert
        $this->assertEquals(0, $libyears);
    }

    public function testCanGetNoNewestVersion()
    {
        //arrange
        $dependency = new Dependency();
        $dependency->current_version->released = new DateTime('-18 months');

        //act
        $libyears = $dependency->getLibyearsBehind();

        //assert
        $this->assertNull($libyears);
    }
}