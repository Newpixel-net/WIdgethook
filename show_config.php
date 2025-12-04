<?php
echo "<pre>";
echo "=== Contents of config.php ===\n\n";
$content = file_get_contents('config.php');
echo htmlspecialchars($content);
echo "</pre>";
