<?php

namespace LibYear\Tests;

use LibYear\Repository;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
	public function testCanGetPackageMetadataURL()
	{
		//arrange
		$repo = new Repository("https://composer.example.com", "/metadata/%package%.json");

		//act
		$url = $repo->getMetadataURL("ecoapm/libyear");

		//assert
		$this->assertEquals("https://composer.example.com/metadata/ecoapm/libyear.json", $url);
	}
}
