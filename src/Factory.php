<?php

namespace ecoAPM\LibYear;

use cli\progress\Bar;
use Garden\Cli\Cli;
use GuzzleHttp\Client;

class Factory
{
	public static function app(): App
	{
		$error_level = error_reporting(E_RECOVERABLE_ERROR);
		$cli = new Cli();
		error_reporting($error_level);

		$fs = new FileSystem();
		$file = new ComposerFile($fs, STDERR);

		$http = new Client();
		$api = new RepositoryAPI($http, STDERR);

		$progress = new Bar("Checking dependencies...", 0);
		$calculator = new Calculator($file, $api, $progress);
		return new App($cli, $calculator, $file, STDOUT);
	}
}
