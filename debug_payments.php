<?php
/**
 * Debug Admin Payments - Test the exact query
 */
ob_start();

const DEBUG = 1;
const MYSQL_DEBUG = 0;
const LOGGING = 0;
const CACHE = 1;
const ALTUMCODE = 66;

require_once __DIR__ . '/app/init.php';
session_start_if_not_started();

ob_end_clean();

header('Content-Type: text/plain');
echo "=== Admin Payments Debug ===\n\n";

// Test basic query first
echo "1. Testing basic payments query:\n";
$result = database()->query("SELECT * FROM `payments` LIMIT 1");
if ($result === false) {
    echo "   FAILED: " . database()->error . "\n";
} else {
    echo "   OK - rows: " . $result->num_rows . "\n";
}

// Test the join query
echo "\n2. Testing join query:\n";
$join_query = "
SELECT
    `payments`.*, `users`.`name` AS `user_name`, `users`.`email` AS `user_email`, `users`.`avatar` AS `user_avatar`
FROM
    `payments`
LEFT JOIN
    `users` ON `payments`.`user_id` = `users`.`user_id`
LIMIT 5
";
$result2 = database()->query($join_query);
if ($result2 === false) {
    echo "   FAILED: " . database()->error . "\n";
} else {
    echo "   OK - rows: " . $result2->num_rows . "\n";
}

// Test count query
echo "\n3. Testing count query:\n";
$count_query = "SELECT COUNT(*) AS `total` FROM `payments` WHERE 1 = 1";
$result3 = database()->query($count_query);
if ($result3 === false) {
    echo "   FAILED: " . database()->error . "\n";
} else {
    $row = $result3->fetch_object();
    echo "   OK - total: " . $row->total . "\n";
}

// Check users table has avatar column
echo "\n4. Checking users.avatar column:\n";
$avatar_check = database()->query("SHOW COLUMNS FROM `users` LIKE 'avatar'");
if ($avatar_check->num_rows > 0) {
    echo "   avatar column EXISTS\n";
} else {
    echo "   avatar column MISSING!\n";
}

// Check payment_processors.php file
echo "\n5. Checking payment_processors.php:\n";
$file = APP_PATH . 'includes/payment_processors.php';
if (file_exists($file)) {
    echo "   File exists\n";
    try {
        $processors = require $file;
        echo "   Loaded OK - " . count($processors) . " processors\n";
    } catch (Throwable $e) {
        echo "   ERROR loading: " . $e->getMessage() . "\n";
    }
} else {
    echo "   FILE MISSING!\n";
}

// Test Filters class
echo "\n6. Testing Filters class:\n";
try {
    $filters = (new \Altum\Filters(
        ['id', 'status', 'plan_id', 'user_id', 'type', 'processor', 'frequency', 'taxes_ids'],
        ['payment_id', 'code'],
        ['id', 'total_amount', 'email', 'datetime', 'name'],
        [],
        ['taxes_ids' => 'json_contains']
    ));
    echo "   Filters created OK\n";
    echo "   SQL WHERE: " . $filters->get_sql_where() . "\n";
} catch (Throwable $e) {
    echo "   FAILED: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// Test Plan model
echo "\n7. Testing Plan model:\n";
try {
    $plans = (new \Altum\Models\Plan())->get_plans();
    echo "   OK - " . count($plans) . " plans\n";
} catch (Throwable $e) {
    echo "   FAILED: " . $e->getMessage() . "\n";
}

// Check if there are any missing columns in payments
echo "\n8. Payments table columns check:\n";
$required_cols = ['id', 'user_id', 'plan_id', 'payment_id', 'payer_id', 'subscription_id',
                  'email', 'name', 'plan', 'processor', 'type', 'frequency', 'billing',
                  'taxes_ids', 'base_amount', 'code', 'discount_amount', 'total_amount',
                  'total_amount_default_currency', 'currency', 'status', 'datetime', 'payment_proof'];

$cols_result = database()->query("SHOW COLUMNS FROM `payments`");
$existing_cols = [];
while ($col = $cols_result->fetch_object()) {
    $existing_cols[] = $col->Field;
}

$missing = array_diff($required_cols, $existing_cols);
if (empty($missing)) {
    echo "   All required columns exist\n";
} else {
    echo "   MISSING columns: " . implode(', ', $missing) . "\n";
}

echo "\n=== Done ===\n";
