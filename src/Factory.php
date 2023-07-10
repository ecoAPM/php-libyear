<?php

namespace ecoAPM\LibYear;

use cli\progress\Bar;
use Garden\Cli\Cli;
use GuzzleHttp\Client;

class Factory
{
	public static function app(): App
	{
		$cli = new Cli();

		$fs = new FileSystem();
		$file = new ComposerFile($fs, STDERR);

		$http = new Client();
		$api = new RepositoryAPI($http, STDERR);

		$progress = new Bar("Checking dependencies...", 0);
		$calculator = new Calculator($file, $api, $progress);
		return new App($cli, $calculator, $file, STDOUT);
	}
}
