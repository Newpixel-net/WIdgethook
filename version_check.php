<?php
// Simple version check - no includes
echo "Version Check\n\n";
echo "File modification time: " . date('Y-m-d H:i:s', filemtime(__FILE__)) . "\n";
echo "deep_debug.php modified: " . date('Y-m-d H:i:s', filemtime('deep_debug.php')) . "\n\n";

// Clear OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache cleared!\n";
}

// Show first 25 lines of deep_debug.php
echo "\nFirst 25 lines of deep_debug.php:\n";
echo "================================\n";
$lines = file('deep_debug.php');
for ($i = 0; $i < min(25, count($lines)); $i++) {
    echo ($i+1) . ": " . $lines[$i];
}
