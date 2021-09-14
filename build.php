<?php

if (!file_exists('dist')) {
    mkdir('dist');
}

$phar = new Phar(__DIR__ . '/dist/libyear.phar');

$dir = new RecursiveDirectoryIterator(__DIR__);
$recursive = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($recursive, '/^((?!tests\/).)+\.php$/', RecursiveRegexIterator::GET_MATCH);

foreach ($files as $file_info) {
    $filename = str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $file_info[0]);
    fwrite(STDOUT, "Adding {$filename}...\n");
    $phar->addFile($filename);
}

$phar->addFile('libyear');
$phar->setDefaultStub('libyear');
