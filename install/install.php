<?php
/**
 * WidgetHook Installation Script
 * Modified to work standalone without license validation
 */

const ALTUMCODE = 66;
define('ROOT_PATH', realpath(__DIR__ . '/..') . '/');
require_once ROOT_PATH . 'app/includes/product.php';

/* Make sure the product wasn't already installed */
if(file_exists(ROOT_PATH . 'install/installed')) {
    die(json_encode([
        'status' => 'error',
        'message' => 'The product is already installed.'
    ]));
}

/* Make sure all the required fields are present */
$required_fields = ['database_host', 'database_name', 'database_username', 'database_password', 'installation_url'];

foreach($required_fields as $field) {
    if(!isset($_POST[$field])) {
        die(json_encode([
            'status' => 'error',
            'message' => 'One of the required fields are missing.'
        ]));
    }
}

foreach(['database_host', 'database_name', 'database_username', 'database_password'] as $key) {
    $_POST[$key] = str_replace('\'', '\\\'', $_POST[$key]);
}

/* Make sure the database details are correct */
mysqli_report(MYSQLI_REPORT_OFF);

try {
    $database = new mysqli(
        $_POST['database_host'],
        $_POST['database_username'],
        $_POST['database_password'],
        $_POST['database_name']
    );
} catch(\Exception $exception) {
    die(json_encode([
        'status' => 'error',
        'message' => 'The database connection has failed: ' . $exception->getMessage()
    ]));
}

if($database->connect_error) {
    die(json_encode([
        'status' => 'error',
        'message' => 'The database connection has failed!'
    ]));
}

$database->set_charset('utf8mb4');

/* Read the local SQL schema file */
$schema_file = ROOT_PATH . 'install/schema.sql';

if(!file_exists($schema_file)) {
    die(json_encode([
        'status' => 'error',
        'message' => 'Schema file not found. Please ensure install/schema.sql exists.'
    ]));
}

$sql_content = file_get_contents($schema_file);

if(empty($sql_content)) {
    die(json_encode([
        'status' => 'error',
        'message' => 'Schema file is empty.'
    ]));
}

/* Prepare the config file content */
$config_content = <<<ALTUM
<?php

/* Configuration of the site */
define('DATABASE_SERVER',   '{$_POST['database_host']}');
define('DATABASE_USERNAME', '{$_POST['database_username']}');
define('DATABASE_PASSWORD', '{$_POST['database_password']}');
define('DATABASE_NAME',     '{$_POST['database_name']}');
define('SITE_URL',          '{$_POST['installation_url']}');

/* Only modify this if you want to use redis for caching instead of the default file system caching */
define('REDIS_IS_ENABLED', 0);
define('REDIS_SOCKET_PATH', null);
define('REDIS_HOST', '127.0.0.1');
define('REDIS_PORT', 6379);
define('REDIS_PASSWORD', null);
define('REDIS_DATABASE', 0);
define('REDIS_TIMEOUT', 2);

ALTUM;

/* Write the new config file */
file_put_contents(ROOT_PATH . 'config.php', $config_content);

/* Run SQL - execute each statement separately */
$database->multi_query($sql_content);

/* Process all results to clear them */
do {
    if($result = $database->store_result()) {
        $result->free();
    }
} while($database->next_result());

/* Check for errors */
if($database->error) {
    die(json_encode([
        'status' => 'error',
        'message' => 'Error when running the database queries: ' . $database->error
    ]));
}

/* Create the installed file */
file_put_contents(ROOT_PATH . 'install/installed', '');

/* Clear language cache */
foreach(glob(ROOT_PATH . 'app/languages/cache/*.php') as $file_path) {
    unlink($file_path);
}

die(json_encode([
    'status' => 'success',
    'message' => ''
]));
