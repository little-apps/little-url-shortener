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
//error_reporting(0);

require_once(dirname(__FILE__).'/config.php');
require_once(dirname(__FILE__).'/functions.php');
require_once(dirname(__FILE__).'/shorturl.class.php');
require_once(dirname(__FILE__).'/facebook-api/autoload.php');

use Facebook\FacebookSession;
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequest;
use Facebook\GraphUser;
use Facebook\FacebookRequestException;

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

// If magic quotes enabled -> remove slashes
if (get_magic_quotes_gpc()) {
	if (!empty($_POST)) {
		foreach ($_POST as $k => $v) {
			$_POST[$k] = stripslashes($v);
		}
	}
	
	if (!empty($_GET)) {
		foreach ($_GET as $k => $v) {
			$_GET[$k] = stripslashes($v);
		}
	}
}

// Start session
session_start();

// Years for registration
$min_year = date('Y', strtotime('-100 years'));
$max_year = date('Y', strtotime('-10 years'));

// Initialize message variables
$messages = array();

// If CSRF token was sent in POST, check if it's valid
if ((isset($_POST['token']) && isset($_SESSION['csrf_token'])) && $_POST['token'] == $_SESSION['csrf_token'])  {
	$csrf_valid = true;
} else {
	$csrf_valid = false;
}

// Generate CSRF Token
$csrf_token = md5(uniqid());
$_SESSION['csrf_token'] = $csrf_token;

// Initialize shorturl class
$shorturl = new ShortURL();

if (!empty($shorturl->error_msg))
	$messages[] = $shorturl->error_msg;

// Admin area
if (defined('LUS_ADMINAREA')) {
	$admin_logged_in = false;

	if (isset($_SESSION['admin_id']) && isset($_SESSION['admin_hash'])) {
		// Lookup user
		$stmt = $mysqli->prepare("SELECT password FROM `".MYSQL_PREFIX."admins` WHERE id = ? LIMIT 0,1");
		$stmt->bind_param('i', $_SESSION['admin_id']);
		$stmt->execute();
		
		$stmt->bind_result($pass_hash);
		
		$user_ip = ((SITE_VALIDATEIP == true) ? $_SERVER['REMOTE_ADDR'] : '');

		if ($stmt->fetch() === true) {
			if ($_SESSION['admin_hash'] == md5($_SESSION['admin_id'].$pass_hash.$user_ip)) {
				$admin_logged_in = true;
			}
		}
		
		$stmt->close();
		
		// Clear results
		unset($pass_hash, $user_ip);
	}
}

// Check if logged in
$logged_in = false;
$fb_logged_in = false;

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

// Check if logged in with Facebook
if (FBLOGIN_ENABLED == true && defined('FBLOGIN_APPID') && defined('FBLOGIN_APPSECRET') ) {
	FacebookSession::setDefaultApplication(FBLOGIN_APPID, FBLOGIN_APPSECRET);
	
	$helper = new FacebookRedirectLoginHelper(SITE_URL . '/login.php');

	try {
		$session = $helper->getSessionFromRedirect();
	} catch(FacebookRequestException $ex) {
		// When Facebook returns an error
		$messages[] = 'There was an error communicating with Facebook. Please try again';
		$session = null;
	} catch(\Exception $ex) {
		// When validation fails or other local issues
		$messages[] = 'There was an error validating your Facebook account. Please try again';
		$session = null;
	}
	
	if ($session) { 
		try {
			$user_profile = (new FacebookRequest($session, 'GET', '/me'))->execute()->getGraphObject(GraphUser::className());
			$permissions = (new FacebookRequest($session, 'GET', '/me/permissions'))->execute()->getGraphObject();

			$granted_permission = array();
			
			foreach ($permissions->asArray() as $v) {
				if ((isset($v->permission) && (isset($v->status))) && $v->status == 'granted')
					$granted_permission[] = $v->permission;
			}
			
			if (!in_array('email', $granted_permission) ||
				!in_array('user_about_me', $granted_permission) ||
				!in_array('user_birthday', $granted_permission)) {
				$session = null;
				$messages[] = 'There seems to be a missing permission from Facebook. Please try logging in again.';
			}
		} catch(FacebookRequestException $e) {
			$session = null;
			$messages[] = 'There was an error accessing your Facebook account. Please try again';
			//echo "Exception occured, code: " . $e->getCode() . " with message: " . $e->getMessage();
		} 
	}
	
	if ($session) {
		if ($logged_in === true) {
			// Were already logged in and validated, no need to query database
			$fb_logged_in = true;
		} else {
			$email = $user_profile->getEmail();
			$fb_first_name = $user_profile->getFirstName();
			$fb_last_name = $user_profile->getLastName();
			$fb_birthdate = $user_profile->getBirthday();
		
			// Lookup user
			$stmt = $mysqli->prepare("SELECT id, password, first_name, last_name, birthday FROM `".MYSQL_PREFIX."users` WHERE email = ? LIMIT 0,1");
			$stmt->bind_param('s', $email);
			$stmt->execute();
			
			$stmt->bind_result($user_id, $pass_hash, $first_name, $last_name, $birthdate);
			
			$user_ip = ((SITE_VALIDATEIP == true) ? $_SERVER['REMOTE_ADDR'] : '');

			if ($stmt->fetch() === true) {
				$stmt->close();
			
				// Is FB info up to date?
				$birthdate_datetime = new DateTime($birthdate);
				if ($fb_first_name != $first_name || $fb_last_name != $last_name || $fb_birthdate != $birthdate_datetime) {
					// Convert birthdate to valid string format
					$birthdate_formatted = sprintf("%04d-%02d-%02d", $fb_birthdate->format('Y'), $fb_birthdate->format('m'), $fb_birthdate->format('d'));
				
					if ($stmt = $mysqli->prepare("UPDATE `".MYSQL_PREFIX."users` SET first_name = ?, last_name = ?, birthday = ? WHERE id = ?")) {
						$stmt->bind_param('sssi', $fb_first_name, $fb_last_name, $birthdate_formatted, $user_id);
						$stmt->execute();
						$stmt->close();
					}
				}
			
				$_SESSION['user_id'] = $user_id;
				$_SESSION['user_hash'] = md5($user_id.$email.$pass_hash.$user_ip);
				
				$logged_in = true;
				$fb_logged_in = true;
			} else {
				// Not registered yet
				$stmt->close();
				
				// Convert birthdate to valid string format
				$birthdate_formatted = sprintf("%04d-%02d-%02d", $fb_birthdate->format('Y'), $fb_birthdate->format('m'), $fb_birthdate->format('d'));
			
				// Hash password
				require_once(dirname(__FILE__).'/passhash.class.php');
				$pass_hash = PassHash::hash(uniqid());
				
				// Generate API Key
				$api_key = md5(uniqid('api_'));
				
				// Generate activation key
				$activate_key = '';
			
				// Insert new user
				$stmt = $mysqli->prepare("INSERT INTO `".MYSQL_PREFIX."users` (`first_name`,`last_name`,`email`,`birthday`,`password`,`api_key`,`activate_key`) VALUES (?,?,?,?,?,?,?)");
				$stmt->bind_param('sssssss', $fb_first_name, $fb_last_name, $email, $birthdate_formatted, $pass_hash, $api_key, $activate_key);
				$stmt->execute();
				$stmt->close();
				
				// Get user ID
				$stmt = $mysqli->prepare("SELECT id FROM `".MYSQL_PREFIX."users` WHERE email = ? LIMIT 0,1");
				$stmt->bind_param('s', $email);
				$stmt->execute();
				
				$stmt->bind_result($user_id);
				
				$stmt->close();
			
				// Login user
				$_SESSION['user_id'] = $user_id;
				$_SESSION['user_hash'] = md5($user_id.$email.$pass_hash.$user_ip);
				$logged_in = true;
				$fb_logged_in = true;
			}

			// Clear results
			unset($email, $pass_hash, $fb_first_name, $fb_last_name, $fb_birthdate, $birthdate_formatted);
		}
	} 

	$fb_login_url = $helper->getLoginUrl(array('email,user_about_me,user_birthday'));
}

if ($logged_in == false) {
	// Clear cookies (if they exist)
	if (isset($_COOKIE['7LSNETUID']) || isset($_COOKIE['7LSNETHASH'])) {
		setcookie("7LSNETUID", "", time()-3600);
		setcookie("7LSNETHASH", "", time()-3600);
	}
}

// Create URL?
if ($csrf_valid == true && isset($_POST['url'])) {
	$url = $_POST['url'];
	
	if ($logged_in)
		$shorturl->set_user_id($_SESSION['user_id']);
	
	if ($shorturl->create($url)) {
		$_SESSION['short_url'] = $shorturl->get_short_url();
	} else {
		$messages[] = $shorturl->error_msg;
	}
}