<?php

define('LUS_LOADED', true);
define('LUS_AJAX', true);

require_once(dirname(__FILE__) . '/inc/config.php');
require_once(dirname(__FILE__) . '/vendor/autoload.php');

// Uncomment this to enable debugging (not recommended)
//error_reporting(E_ALL);

session_start();

// Prevent caching
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: ".gmdate( "D, d M Y H:i:s" )." GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-type: application/json");

if (!isset($_REQUEST['token']))
	die(json_encode(array('status' => 'error', 'message' => 'Token is not specified.')));

if ($_REQUEST['token'] != $_SESSION['ajax_token'] || time() > $_SESSION['ajax_expires'])
	die(json_encode(array('status' => 'error', 'message' => 'Token is not valid.')));

// Connect to MySQL
$mysqli = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);

if (mysqli_connect_error()) {
    die(json_encode(array('status' => 'error', 'message' => 'Unable to connect to database.')));
}

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
	die(json_encode(array('status' => 'error', 'message' => 'Request not sent using AJAX.')));
}

if (empty($_REQUEST['action'])) {
	die(json_encode(array('status' => 'error', 'message' => 'No action specified.')));
}

$response = '';

switch(strtolower($_REQUEST['action'])) {
	case 'stats': {
		$stmt = $mysqli->prepare("SELECT COUNT(*) FROM `".MYSQL_PREFIX."users`");
		$stmt->execute();
		$stmt->bind_result($user_count);
		$stmt->fetch();
		$stmt->close();


		$stmt = $mysqli->prepare("SELECT COUNT(*), SUM(visits) FROM `".MYSQL_PREFIX."urls`");
		$stmt->execute();
		$stmt->bind_result($url_count, $visit_count);
		$stmt->fetch();
		$stmt->close();

		$user_count = ( is_numeric($user_count) ? $user_count : 0 );
		$url_count = ( is_numeric($url_count) ? $url_count : 0 );
		$visit_count = ( is_numeric($visit_count) ? $visit_count : 0 );

		$response = json_encode(array('users' => $user_count, 'urls' => $url_count, 'visits' => $visit_count));

		break;
	}
	
	case 'generate': {
		if (empty($_POST['url'])) {
			$response = json_encode(array('status' => 'error', 'message' => 'No URL specified.'));
		} else {
			// Initialize shorturl class
			require_once(dirname(__FILE__) . '/inc/shorturl.class.php');
			$shorturl = new ShortURL();
			
			$url = $_POST['url'];
			
			$logged_in = false;
			
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
			
			if ($logged_in)
				$shorturl->set_user_id($_SESSION['user_id']);
		
			if ($shorturl->create($url)) {
				$short_url = $shorturl->get_short_url();
				
				ob_start();
				\PHPQRCode\QRcode::png($short_url, null, \PHPQRCode\Constants::QR_ECLEVEL_L, 7);
				$image = base64_encode(ob_get_contents());
				ob_end_clean();
				
				$response = json_encode(array('status' => 'success', 'short_url' => $short_url, 'qrcode' => 'data:image/png;base64,' . $image));
			} else {
				$response = json_encode(array('status' => 'error', 'message' => $shorturl->error_msg));
			}
		}
		
		break;
	}
	
	default: {
		$response = json_encode(array('status' => 'error', 'message' => 'Invalid action.'));
	}
}

echo $response;