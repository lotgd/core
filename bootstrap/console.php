<?php

use LotGD\Core\Console\Main as DaenerysConsole;

function includeIfExists($file) 
{
    if (file_exists($file)) {
        return include $file;
    }
}

// Dance to find the autoloader.
// TOOD: change this to open up the Composer config and use $c['config']['vendor-dir'] instead of "vendor"
includeIfExists(getcwd() . '/vendor/autoload.php') ||
includeIfExists(__DIR__ . '/../vendor/autoload.php') ||
includeIfExists(__DIR__ . '/../autoload.php');


return new DaenerysConsole();