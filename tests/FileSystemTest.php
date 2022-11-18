<?php

namespace LibYear\Tests;

use LibYear\FileSystem;
use PHPUnit\Framework\TestCase;

class FileSystemTest extends TestCase
{
	public function testExistsWhenFileExists()
	{
		//arrange
		$file_system = new FileSystem();

		//act
		$exists = $file_system->exists(__DIR__ . '/../composer.json');

		//assert
		$this->assertTrue($exists);
	}

	public function testDoesNotExistWhenFileDoesNot()
	{
		//arrange
		$file_system = new FileSystem();

		//act
		$exists = $file_system->exists(__DIR__ . '/../composer2.json');

		//assert
		$this->assertFalse($exists);
	}

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
