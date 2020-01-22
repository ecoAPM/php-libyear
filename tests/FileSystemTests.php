<?php

namespace Libyear\Tests;

use Libyear\FileSystem;
use PHPUnit\Framework\TestCase;

class FileSystemTests extends TestCase
{
    public function testCanReadJSON()
    {
        //arrange
        $file_system = new FileSystem();

        //act
        $composer_json = $file_system->getJSON(__DIR__ . '/../composer.json');

        //assert
        $this->assertContains('libyear', $composer_json['name']);
    }
}