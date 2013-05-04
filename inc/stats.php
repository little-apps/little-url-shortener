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

require_once('config.php');

// Connect to MySQL
$mysqli = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);

if (mysqli_connect_error()) {
    die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
}

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

// Prevent caching
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: ".gmdate( "D, d M Y H:i:s" )." GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-type: application/json");

echo json_encode(array('users' => $user_count, 'urls' => $url_count, 'visits' => $visit_count));