<?php
// Check what init.php has on the server
echo "<pre>";
echo "Contents of app/init.php lines 50-75:\n";
echo "=====================================\n";

$lines = file('app/init.php');
for ($i = 49; $i < min(75, count($lines)); $i++) {
    echo ($i+1) . ": " . htmlspecialchars($lines[$i]);
}

echo "\n\nChecking app/traits/ folder:\n";
echo "============================\n";
$files = glob('app/traits/*.php');
foreach ($files as $f) {
    echo "Found: $f\n";
}

echo "\nDone\n";
