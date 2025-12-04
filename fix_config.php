<?php
/**
 * Fix config.php - Remove duplicate constant definitions
 *
 * The config.php should ONLY contain database credentials and SITE_URL.
 * Constants like DEBUG, LOGGING, CACHE should be in index.php only.
 *
 * Access: /fix_config.php?key=widgethook_reset_2024
 */

define('FIX_KEY', 'widgethook_reset_2024');

if (!isset($_GET['key']) || $_GET['key'] !== FIX_KEY) {
    http_response_code(403);
    die('Unauthorized. Usage: fix_config.php?key=widgethook_reset_2024');
}

header('Content-Type: text/plain');
echo "=== Config.php Fixer ===\n\n";

$config_file = __DIR__ . '/config.php';

if (!file_exists($config_file)) {
    die("ERROR: config.php not found!\n");
}

// Read current config
$content = file_get_contents($config_file);
echo "Current config.php:\n";
echo "-------------------\n";
echo $content;
echo "\n-------------------\n\n";

// Check for problematic lines
$problematic_patterns = [
    '/^\s*(define\s*\(\s*[\'"]DEBUG[\'"]\s*,.*\);?\s*)$/mi',
    '/^\s*(define\s*\(\s*[\'"]MYSQL_DEBUG[\'"]\s*,.*\);?\s*)$/mi',
    '/^\s*(define\s*\(\s*[\'"]LOGGING[\'"]\s*,.*\);?\s*)$/mi',
    '/^\s*(define\s*\(\s*[\'"]CACHE[\'"]\s*,.*\);?\s*)$/mi',
    '/^\s*(define\s*\(\s*[\'"]ALTUMCODE[\'"]\s*,.*\);?\s*)$/mi',
    '/^\s*(const\s+DEBUG\s*=.*;\s*)$/mi',
    '/^\s*(const\s+MYSQL_DEBUG\s*=.*;\s*)$/mi',
    '/^\s*(const\s+LOGGING\s*=.*;\s*)$/mi',
    '/^\s*(const\s+CACHE\s*=.*;\s*)$/mi',
    '/^\s*(const\s+ALTUMCODE\s*=.*;\s*)$/mi',
];

$found_issues = [];
foreach ($problematic_patterns as $pattern) {
    if (preg_match($pattern, $content, $matches)) {
        $found_issues[] = trim($matches[1]);
    }
}

if (empty($found_issues)) {
    echo "No problematic constant definitions found in config.php.\n";
    echo "Config.php looks clean!\n";
    exit;
}

echo "Found problematic lines that should be removed:\n";
foreach ($found_issues as $issue) {
    echo "  - {$issue}\n";
}

// Only fix if explicitly requested
if (!isset($_GET['fix'])) {
    echo "\nTo apply the fix, add &fix=1 to the URL:\n";
    echo "fix_config.php?key=widgethook_reset_2024&fix=1\n";
    exit;
}

echo "\n=== Applying Fix ===\n";

// Create backup
$backup_file = __DIR__ . '/config.php.bak.' . date('Y-m-d-His');
if (copy($config_file, $backup_file)) {
    echo "Backup created: " . basename($backup_file) . "\n";
} else {
    die("ERROR: Could not create backup!\n");
}

// Remove problematic lines
$new_content = $content;
foreach ($problematic_patterns as $pattern) {
    $new_content = preg_replace($pattern, '', $new_content);
}

// Clean up multiple blank lines
$new_content = preg_replace("/\n{3,}/", "\n\n", $new_content);

// Write fixed config
if (file_put_contents($config_file, $new_content)) {
    echo "Config.php has been fixed!\n\n";
    echo "New config.php:\n";
    echo "-------------------\n";
    echo $new_content;
    echo "\n-------------------\n";
} else {
    echo "ERROR: Could not write to config.php!\n";
}

echo "\n=== Done ===\n";
echo "Now test the dashboard at /dashboard\n";
