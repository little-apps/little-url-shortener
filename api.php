<?php
/*
    Little URL Shortener
    Copyright (C) 2008 Little Apps  (http://www.little-apps.org/)

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

define('LUS_LOADED', true);

require_once('inc/config.php');
require_once('inc/shorturl.class.php');

// Uncomment this to enable debugging (not recommended)
//error_reporting(0);

// Connect to MySQL
$mysqli = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);

if (mysqli_connect_error()) {
    die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
}

header('Content-Type: text/json');

if (!defined('API_ENABLE') || !API_ENABLE)
	die(json_encode(array('status' => 'error', 'message' => 'The API is currently disabled.')));

$shorturl = new ShortURL();

if (!empty($shorturl->error_msg))
	die(json_encode(array('status' => 'error', 'message' => $shorturl->error_msg)));

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

if ($_GET['request'] == 'create' && (!defined('API_WRITE') || !API_WRITE)) {
	die(json_encode(array('status' => 'error', 'message' => 'Generating short URLs is disabled.')));
}

if ($_GET['request'] == 'get' && (!defined('API_READ') || !API_READ)) {
	die(json_encode(array('status' => 'error', 'message' => 'Converting short URLs to long URLs is disabled.')));
}

// Check URL
if (!isset($_GET['url'])) {
	die(json_encode(array('status' => 'error', 'message' => 'No URL sent')));
}

// No need to use urldecode() since it's already taken care of
$url = $_GET['url'];
$api_key = ( isset($_GET['api_key']) ? trim(strtolower($_GET['api_key'])) : '' );

$user_id = 0;

if ($api_key != '') {
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

if ($user_id == 0 && defined('API_AUTHORIZED') && API_AUTHORIZED)
	die(json_encode(array('status' => 'error', 'message' => 'A valid API key is required.')));

if ($_GET['request'] == 'create') {
	if ($user_id > 0)
		$shorturl->set_user_id($user_id);
	
	if ($shorturl->create($url)) {
		$short_url = $shorturl->get_short_url();
		$data = array('status' => 'success', 'shorturl' => $short_url, 'longurl' => $url);
	} else {
		$data = json_encode(array('status' => 'error', 'message' => $shorturl->error_msg));
	}
	
	// Return JSON
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
	
	if (strlen($path) > 8) {
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