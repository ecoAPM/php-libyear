<?php

namespace LibYear\Tests;

use LibYear\FileSystem;
use PHPUnit\Framework\TestCase;

class FileSystemTest extends TestCase
{
	public function testCanReadJSON()
	{
		//arrange
		$file_system = new FileSystem();

		//act
		$composer_json = $file_system->getJSON(__DIR__ . '/../composer.json');

		//assert
		$this->assertStringContainsString('libyear', $composer_json['name']);
	}
}
