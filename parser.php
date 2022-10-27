<?php
namespace php_shell;
require __DIR__ . '/vendor/autoload.php';

use App\Parser;

$GLOBALS['config'] = require_once 'config.php';
require_once 'helpers.php';

$start = microtime(true);
$rates = [];

try {
    $rates = loadRates();
} catch (\Exception $e) {
    echo "\033[31m" . $e->getMessage() . "\n";
    echo "\33[0;36mA local source was used to calculate the rates.\n";
    $rates = $GLOBALS['config']['rates'];
}

// Checking the required arguments for the command line.
if (!isset($argv[1])) {
    die("\033[31mMissing required input file\n");
}

// Get arguments
$file = $argv[1];

// Parse file
(new Parser($file, $rates))->parse();

printf(
        "Total time: %s\r\nMemory Used (current): %s\r\nMemory Used (peak): %s", 
        round(microtime(true) - $start, 4), formatBytes(memory_get_usage()), formatBytes(memory_get_peak_usage()) . "\n"
);
