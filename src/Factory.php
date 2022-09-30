<?php

namespace LibYear;

use GuzzleHttp\Client;

class Factory
{
	public static function app(): App
	{
		$fs = new FileSystem();
		$file = new ComposerFile($fs);

		$http = new Client();
		$api = new RepositoryAPI($http, STDERR);

		$calculator = new Calculator($file, $api);
		return new App($calculator, STDOUT);
	}
}