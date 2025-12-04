<?php
const ALTUMCODE = 66;
define('ROOT_PATH', realpath(__DIR__ . '/..') . '/');
require_once ROOT_PATH . 'vendor/autoload.php';
require_once ROOT_PATH . 'app/includes/product.php';

function get_ip() {
	if(array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {

		if(strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',')) {
			$ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

			return trim(reset($ips));
		} else {
			return $_SERVER['HTTP_X_FORWARDED_FOR'];
		}

	} else if(array_key_exists('REMOTE_ADDR', $_SERVER)) {
		return $_SERVER['REMOTE_ADDR'];
	} else if(array_key_exists('HTTP_CLIENT_IP', $_SERVER)) {
		return $_SERVER['HTTP_CLIENT_IP'];
	}

	return '';
}

$altumcode_api = 'https://api2.altumcode.com/validate';

/* Make sure the product wasn't already installed */
if(file_exists(ROOT_PATH . 'install/installed')) {
	die();
}

/* Make sure all the required fields are present */
$required_fields = ['license_key', 'database_host', 'database_name', 'database_username', 'database_password', 'installation_url'];

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

/* Make sure the license is correct */
$response = \Unirest\Request::post($altumcode_api, [], [
	'type'              => 'installation',
	'license_key'       => $_POST['license_key'],
	'installation_url'  => $_POST['installation_url'],
	'product_key'       => PRODUCT_KEY,
	'product_name'      => PRODUCT_NAME,
	'product_version'   => '53.0.0',
	'server_ip'         => $_SERVER['SERVER_ADDR'],
	'client_ip'         => get_ip(),
	'newsletter_email'  => $_POST['newsletter_email'],
	'newsletter_name'   => $_POST['newsletter_name']
]);

if(!isset($response->body->status)) {
	die(json_encode([
		'status' => 'error',
		'message' => $response->raw_body
	]));
}

if($response->body->status == 'error') {
	die(json_encode([
		'status' => 'error',
		'message' => $response->body->message
	]));
}

/* Success check */
if($response->body->status == 'success') {

	/* Prepare the config file content */
	$config_content =
		<<<ALTUM
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

	/* Run SQL */
	$dump = array_filter(explode('-- SEPARATOR --', $response->body->sql));

	foreach($dump as $query) {
		$database->query($query);

		if($database->error) {
			die(json_encode([
				'status' => 'error',
				'message' => 'Error when running the database queries: ' . $database->error
			]));
		}
	}

	/* Create the installed file */
	file_put_contents(ROOT_PATH . 'install/installed', '');

	/* Determine all the languages available in the directory */
	foreach(glob(ROOT_PATH . 'app/languages/cache/*.php') as $file_path) {
		unlink($file_path);
	}

	die(json_encode([
		'status' => 'success',
		'message' => ''
	]));
}
