<?php

namespace ecoAPM\LibYear\Tests;

use ecoAPM\LibYear\App;
use ecoAPM\LibYear\Factory;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
	public function testCanCreateApp()
	{
		//act
		$app = Factory::app();

		//assert
		$this->assertInstanceOf(App::class, $app);
	}
}
