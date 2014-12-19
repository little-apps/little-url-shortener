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

error_reporting(0);
session_start();

if (!isset($_GET['token'])) {
	die('No token specifed');
} else if ($_GET['token'] != $_SESSION['image_token']) {
	die('Invalid token');
} else if (!isset($_SESSION['short_url'])) {
	die('No short URL found');
}

require_once(dirname(__FILE__).'/phpqrcode/lib/PHPQRCode.php');

\PHPQRCode\Autoloader::register();

// Prevent caching
header( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
header( "Last-Modified: ".gmdate( "D, d M Y H:i:s" )." GMT" );
header( "Cache-Control: no-store, no-cache, must-revalidate" );
header( "Cache-Control: post-check=0, pre-check=0", false );
header( "Pragma: no-cache" );
header( "Content-type: image/png" );

\PHPQRCode\QRcode::png($_SESSION['short_url'], false, QR_ECLEVEL_L, 7);

// Unset variables
//unset($_SESSION['image_token']);
unset($_SESSION['short_url']);