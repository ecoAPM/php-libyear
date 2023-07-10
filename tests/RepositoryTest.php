<?php

namespace ecoAPM\LibYear\Tests;

use ecoAPM\LibYear\Repository;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
	public function testCanGetPackageMetadataURL()
	{
		//arrange
		$repo = new Repository("https://composer.example.com/packages", "/metadata/%package%.json");

		//act
		$url = $repo->getMetadataURL("ecoapm/libyear");

		//assert
		$this->assertEquals("https://composer.example.com/metadata/ecoapm/libyear.json", $url);
	}

	public function testCanGetDefaultPackageMetadataURL()
	{
		//arrange
		$repo = new Repository("https://composer.example.com/packages", null);

		//act
		$url = $repo->getMetadataURL("ecoapm/libyear");

		//assert
		$this->assertEquals("https://composer.example.com/packages/ecoapm/libyear.json", $url);
	}
}
