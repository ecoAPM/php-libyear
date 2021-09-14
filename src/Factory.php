<?php

namespace LibYear;

use GuzzleHttp\Client;
use LibYear\Calculator;
use LibYear\ComposerFile;
use LibYear\FileSystem;
use LibYear\PackagistAPI;

class Factory
{
    public static function App()
    {
        $fs = new FileSystem();
        $file = new ComposerFile($fs);

        $http = new Client();
        $api = new PackagistAPI($http);

        $calculator = new Calculator($file, $api);
        return new App($calculator, STDOUT);
    }
}