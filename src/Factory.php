<?php

namespace LibYear;

use cli\progress\Bar;
use GuzzleHttp\Client;

class Factory
{
	public static function app(): App
	{
		$fs = new FileSystem();
		$file = new ComposerFile($fs, STDERR);

		$http = new Client();
		$api = new RepositoryAPI($http, STDERR);

		$progress = new Bar("Checking dependencies...", 0);
		$calculator = new Calculator($file, $api, $progress);
		return new App($calculator, STDOUT);
	}
}
