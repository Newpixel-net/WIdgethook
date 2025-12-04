<?php
/*
 * Test AJAX Response - See exactly what the endpoint returns
 * DELETE THIS FILE AFTER DEBUGGING
 */

echo "<h1>Test AJAX Response</h1><pre>";

// Clear OPcache first
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache cleared.\n\n";
}

// Make a request to the actual AJAX endpoint
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$ajax_url = $base_url . "/admin/index/get_stats_ajax";

echo "Testing: $ajax_url\n\n";

// Get cookies from current request to pass session
$cookies = [];
foreach ($_COOKIE as $name => $value) {
    $cookies[] = "$name=$value";
}
$cookie_header = implode('; ', $cookies);

// Make the request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $ajax_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIE, $cookie_header);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Requested-With: XMLHttpRequest'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $http_code\n";
if ($error) {
    echo "cURL Error: $error\n";
}

echo "\n=== RAW RESPONSE (first 2000 chars) ===\n";
echo htmlspecialchars(substr($response, 0, 2000));

echo "\n\n=== JSON DECODE TEST ===\n";
$decoded = json_decode($response);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "Valid JSON!\n";
    echo "Status: " . ($decoded->status ?? 'N/A') . "\n";
    if (isset($decoded->details)) {
        echo "Details present: Yes\n";
        print_r($decoded->details);
    }
} else {
    echo "JSON Error: " . json_last_error_msg() . "\n";
    echo "\nThe response is NOT valid JSON. Look for PHP errors/warnings above.\n";
}

echo "\n=== Check if logged in ===\n";
echo "Session cookie present: " . (isset($_COOKIE['PHPSESSID']) ? 'Yes' : 'No') . "\n";

echo "</pre>";
