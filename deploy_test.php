<?php
/**
 * Deployment verification file
 * Access this at: yoursite.com/deploy_test.php
 */

echo "<h1>Deployment Test</h1>";
echo "<p><strong>Server Time:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>File Last Modified:</strong> " . date('Y-m-d H:i:s', filemtime(__FILE__)) . "</p>";
echo "<p><strong>Deploy Marker:</strong> v2024120401</p>";
echo "<hr>";
echo "<p>If you see this with marker <code>v2024120401</code>, deployment is working correctly.</p>";
