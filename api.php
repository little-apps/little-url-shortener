<?php
define('LUS_LOADED', true);

require_once('inc/config.php');

// Connect to MySQL
$mysqli = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);

if (mysqli_connect_error()) {
    die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
}

header('Content-Type: text/json');

if ( $_SERVER['REQUEST_METHOD'] && $_SERVER['REQUEST_METHOD'] != 'GET' ) {
    header( 'Allow: GET', true, 405 );
    
	die(json_encode(array('status' => 'error', 'message' => 'Request method must be GET')));
}

// Check request
if (!isset($_GET['request'])) {
	die(json_encode(array('status' => 'error', 'message' => 'API request is empty')));
}

if ($_GET['request'] != 'create' && $_GET['request'] != 'get') {
	die(json_encode(array('status' => 'error', 'message' => 'Invalid API request (must be create or get)')));
}

// Check URL
if (!isset($_GET['url'])) {
	die(json_encode(array('status' => 'error', 'message' => 'No URL sent')));
}

// No need to use urldecode() since it's already taken care of
$url = $_GET['url'];

if ($_GET['request'] == 'create') {
	if (!filter_var($url, FILTER_VALIDATE_URL)) {
		die(json_encode(array('status' => 'error', 'message' => 'URL is invalid')));
	}

	// Parse URL
	$url_parts = parse_url($url);

	// Make sure its http or https
	if (!isset($url_parts['scheme'])) {
		die(json_encode(array('status' => 'error', 'message' => 'No URL scheme specified')));
	} else if (strtolower($url_parts['scheme']) != 'http' && strtolower($url_parts['scheme']) != 'https') {
		die(json_encode(array('status' => 'error', 'message' => 'URL scheme is invalid')));
	}
	
	if (substr_compare($url, SITE_URL, 0, strlen(SITE_URL), true) == 0 || substr_compare($url, SITE_SSLURL, 0, strlen(SITE_SSLURL), true) == 0) {
		die(json_encode(array('status' => 'error', 'message' => 'Cannot shorten URL for another shortened URL')));
	}

	// Is it a secure URL?
	$is_ssl_url = ( (strtolower($url_parts['scheme']) == 'https') ? true : false );

	$api_key = ( isset($_GET['api_key']) ? trim(strtolower($_GET['api_key'])) : '' );

	$user_id = 0;

	if ($logged_in == true) {
		$user_id = $_SESSION['user_id'];
	} else if ($api_key != '') {
		// Lookup user using API key
		$stmt = $mysqli->prepare("SELECT id FROM `".MYSQL_PREFIX."users` WHERE api_key = ? LIMIT 0,1");
		$stmt->bind_param('s', $api_key);
		$stmt->execute();
		
		$stmt->bind_result($user_id);

		if ($stmt->fetch() !== true) {
			$user_id = 0;
		}

		$stmt->close();
	}

	// Generate short URL
	$short_url_path = '';
	
	$salt = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
	$salt_len = strlen( $salt );

	mt_srand();

	for ( $i = 0; $i < SITE_SHORTURLLENGTH; $i++ ) {
		$chr = $salt[ mt_rand( 0, $salt_len - 1 ) ];
		$short_url_path .= $chr;
	}
	
	$stmt = $mysqli->prepare("INSERT INTO `".MYSQL_PREFIX."urls` (`short_url`,`long_url`,`user`,`visits`) VALUES (?,?,?,0)");
	$stmt->bind_param('ssi', $short_url_path, $url, $user_id);
	$stmt->execute();
	
	while ($stmt->affected_rows !== 1) {
		// Regenerate short URL
		$short_url_path = '';

		mt_srand();

		for ( $i = 0; $i < SITE_SHORTURLLENGTH; $i++ ) {
			$chr = $salt[ mt_rand( 0, $salt_len - 1 ) ];
			$short_url_path .= $chr;
		}
		
		$stmt->reset();
		$stmt->execute();
	}
	
	$stmt->close();
	
	if ($is_ssl_url)
		$short_url = SITE_SSLURL . '/' . $short_url_path;
	else
		$short_url = SITE_URL . '/' . $short_url_path;
	
	// Return JSON
	$data = array('status' => 'success', 'shorturl' => $short_url, 'longurl' => $url);
	
	echo stripslashes(json_encode($data));
} else if ($_GET['request'] == 'get') {
	$path = '';

	// Check if long url is URL or path
	if (filter_var($url, FILTER_VALIDATE_URL)) {
		// Parse URL
		$url_parts = parse_url($url);
		
		if (isset($url_parts['path']))
			$path = substr($url_parts['path'], 1, 7);
	} else {
		$path = $url;
	}
	
	if (strlen($path) != SITE_SHORTURLLENGTH) {
		die(json_encode(array('status' => 'error', 'message' => 'Short URL is invalid')));
	}
	
	// Lookup using path
	$stmt = $mysqli->prepare("SELECT long_url FROM `".MYSQL_PREFIX."urls` WHERE short_url = ? LIMIT 0,1");
	$stmt->bind_param('s', $path);
	$stmt->execute();
	
	$stmt->bind_result($long_url);

	if ($stmt->fetch() !== true) {
		die(json_encode(array('status' => 'error', 'message' => 'Short URL was not found')));
	}

	$stmt->close();
	
	// Parse URL
	$url_parts = parse_url($long_url);
	
	// If long URL is secure -> return secure short URL
	if (strtolower($url_parts['scheme']) == 'https')
		$short_url = SITE_SSLURL . '/' . $path;
	else
		$short_url = SITE_URL . '/' . $path;
	
	$data = array('status' => 'success', 'shorturl' => $short_url, 'longurl' => $long_url);
	echo stripslashes(json_encode($data));
}