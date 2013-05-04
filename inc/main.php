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

if (!defined('LUS_LOADED')) die('This file cannot be loaded directly');

// Uncomment this to enable debugging (not recommended)
error_reporting(0);

require_once(dirname(__FILE__).'/config.php');
require_once(dirname(__FILE__).'/functions.php');

if (file_exists('install.php')) {
	die('The file install.php must be removed before using this script.');
}

// Make sure MySQLi extension exists
if (!extension_loaded('mysqli')) die('The MySQLi extension for PHP is not installed.');

// Connect to MySQL
$mysqli = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);

if (mysqli_connect_error()) {
    die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
}

// Start session
session_start();

// Years for registration
$min_year = date('Y', strtotime('-100 years'));
$max_year = date('Y', strtotime('-10 years'));

// If CSRF token was sent in POST, check if it's valid
if ((isset($_POST['token']) && isset($_SESSION['csrf_token'])) && $_POST['token'] == $_SESSION['csrf_token'])  {
	$csrf_valid = true;
} else {
	$csrf_valid = false;
}

// Generate CSRF Token
$csrf_token = md5(uniqid());
$_SESSION['csrf_token'] = $csrf_token;

// Check if logged in
$logged_in = false;

if (isset($_COOKIE['7LSNETUID']) && isset($_COOKIE['7LSNETHASH'])) {
	$_SESSION['user_id'] = $_COOKIE['7LSNETUID'];
	$_SESSION['user_hash'] = $_COOKIE['7LSNETHASH'];
}

if (isset($_SESSION['user_id']) && isset($_SESSION['user_hash'])) {
	// Lookup user
	$stmt = $mysqli->prepare("SELECT email, password FROM `".MYSQL_PREFIX."users` WHERE id = ? LIMIT 0,1");
	$stmt->bind_param('i', $_SESSION['user_id']);
	$stmt->execute();
	
	$stmt->bind_result($user_email, $pass_hash);
	
	$user_ip = ((SITE_VALIDATEIP == true) ? $_SERVER['REMOTE_ADDR'] : '');

	if ($stmt->fetch() === true) {
		if ($_SESSION['user_hash'] == md5($_SESSION['user_id'].$user_email.$pass_hash.$user_ip)) {
			$logged_in = true;
		}
	}
	
	$stmt->close();
	
	// Clear results
	unset($user_email);
	unset($pass_hash);
}

if ($logged_in == false) {
	// Clear cookies (if they exist)
	if (isset($_COOKIE['7LSNETUID']) || isset($_COOKIE['7LSNETHASH'])) {
		setcookie("7LSNETUID", "", time()-3600);
		setcookie("7LSNETHASH", "", time()-3600);
	}
}

// Initilaze message variables
$messages = array();

// Create URL?
if ($csrf_valid == true && isset($_POST['url'])) {
	$url = $_POST['url'];

	if (!filter_var($url, FILTER_VALIDATE_URL)) {
		$messages[] = 'URL is invalid';
	} else {
		// Parse URL
		$url_parts = parse_url($url);
		
		// If no scheme -> add http to it. Otherwise, make sure its http or https
		if (!isset($url_parts['scheme'])) {
			$messages[] = 'No URL scheme specified';
		} else if (strtolower($url_parts['scheme']) != 'http' && strtolower($url_parts['scheme']) != 'https') {
			$messages[] = 'URL scheme is invalid';
		}
		
		if ((strlen($url) >= strlen(SITE_SSLURL)) && substr_compare($url, SITE_URL, 0, strlen(SITE_URL), true) == 0 || substr_compare($url, SITE_SSLURL, 0, strlen(SITE_SSLURL), true) == 0) {
			$messages[] = 'Cannot shorten URL for another shortened URL';
		}
	
	}

	if (count($messages) == 0) {
		// Is it a secure URL?
		$is_ssl_url = ( (strtolower($url_parts['scheme']) == 'https') ? true : false );
	
		// Get user id
		if ($logged_in == true) {
			$user_id = $_SESSION['user_id'];
		} else {
			$user_id = 0;
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
			
		$_SESSION['short_url'] = $short_url;
		$_SESSION['image_token'] = md5(uniqid('image_'));
	}
}