<?php

if(!file_exists('dist'))
    mkdir('dist');

$phar = new Phar(__DIR__ . '/dist/libyear.phar');

$files = new RegexIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__)), '/^((?!tests\/).)+\.php$/', RecursiveRegexIterator::GET_MATCH);

foreach ($files as $file_info) {
    {
        $filename = str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $file_info[0]);
        echo "Adding {$filename}...\n";
        $phar->addFile($filename);
    }
}

$phar->setDefaultStub('libyear.php');