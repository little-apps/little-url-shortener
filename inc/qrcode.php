<?php
error_reporting(0);
session_start();

if (!isset($_GET['token'])) {
	die('No token specifed');
} else if ($_GET['token'] != $_SESSION['image_token']) {
	die('Invalid token');
} else if (!isset($_SESSION['short_url'])) {
	die('No short URL found');
}

require_once(dirname(__FILE__).'/phpqrcode/qrlib.php');

// Prevent caching
header( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
header( "Last-Modified: ".gmdate( "D, d M Y H:i:s" )." GMT" );
header( "Cache-Control: no-store, no-cache, must-revalidate" );
header( "Cache-Control: post-check=0, pre-check=0", false );
header( "Pragma: no-cache" );
header( "Content-type: image/png" );

QRcode::png($_SESSION['short_url'], false, QR_ECLEVEL_L, $size = 7);

// Unset variables
//unset($_SESSION['image_token']);
unset($_SESSION['short_url']);