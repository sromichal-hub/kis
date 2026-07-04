<?php

// Load composer autoloader
require __DIR__ . '/../vendor/autoload.php';

// Clear test cache before running tests
$testCacheDir = __DIR__ . '/../var/cache/test';
if (is_dir($testCacheDir)) {
    array_map('unlink', glob("$testCacheDir/*.*"));
    @rmdir($testCacheDir);
}

// Ensure var directory exists
@mkdir(__DIR__ . '/../var', 0777, true);
@mkdir(__DIR__ . '/../var/cache', 0777, true);
@mkdir(__DIR__ . '/../var/log', 0777, true);


