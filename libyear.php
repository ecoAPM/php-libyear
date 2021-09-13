<?php

use cli\Table;
use GuzzleHttp\Client;
use LibYear\Calculator;
use LibYear\ComposerFile;
use LibYear\Dependency;
use LibYear\FileSystem;
use LibYear\PackagistAPI;

echo "Gathering information...\n";

require_once __DIR__ . '/vendor/autoload.php';

$calculator = new Calculator(new ComposerFile(new FileSystem()), new PackagistAPI(new Client()));
$dependencies = $calculator->getDependencyInfo($argv[1] ?? '.');

$quiet_mode = in_array('-q', $argv);
if($quiet_mode)
    $dependencies = array_filter($dependencies, fn(Dependency $dependency): bool => $dependency->getLibyearsBehind() > 0);

$table = new Table(['Package', 'Current Version', 'Released', 'Newest Version', 'Released', 'Libyears Behind'],
    array_map(fn(Dependency $dependency): array => [
        $dependency->name,
        $dependency->current_version->version_number,
        isset($dependency->current_version->released) ? $dependency->current_version->released->format('Y-m-d') : null,
        isset($dependency->newest_version->version_number) ? $dependency->newest_version->version_number : null,
        isset($dependency->newest_version->released) ? $dependency->newest_version->released->format('Y-m-d') : null,
        $dependency->getLibyearsBehind() !== null ? number_format($dependency->getLibyearsBehind(), 2) : null
    ], $dependencies));

$table->display();
echo 'Total: ' . number_format(Calculator::getTotalLibyearsBehind($dependencies), 2) . " libyears behind\n";