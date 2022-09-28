<?php

namespace LibYear;

use GuzzleHttp\Client;
use function cli\err;

class Factory
{
	public static function App(): App
	{
		$fs = new FileSystem();
		$file = new ComposerFile($fs);

		$http = new Client();
		$api = new PackagistAPI($http, STDERR);

		$calculator = new Calculator($file, $api);
		return new App($calculator, STDOUT);
	}
}