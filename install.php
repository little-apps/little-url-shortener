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

	// Is there already a config.php file?
	if (file_exists('inc/config.php')) {
		define('LUS_LOADED', true);
		require_once('inc/config.php');
	}
	
	$default_values = array(
		'site' => array(
			'url' => ( defined('SITE_URL') ? SITE_URL : 'http://' . $_SERVER['SERVER_NAME'] ),
			'ssl-url' => ( defined('SITE_SSLURL') ? SITE_SSLURL : 'https://' . $_SERVER['SERVER_NAME'] ),
			'name' => ( defined('SITE_NAME') ? SITE_NAME : $_SERVER['SERVER_NAME'] ),
			'noreply-email' => ( defined('SITE_NOREPLY') ? SITE_NOREPLY : 'noreply@' . $_SERVER['SERVER_NAME'] ),
			'admin-email' => ( defined('SITE_ADMINEMAIL') ? SITE_ADMINEMAIL : 'webmaster@' . $_SERVER['SERVER_NAME'] ),
			'shorturl-length' => ( defined('SITE_SHORTURLLENGTH') ? SITE_SHORTURLLENGTH : '7' ),
			'validate-ip' => ( defined('SITE_VALIDATEIP') ? SITE_VALIDATEIP : true ),
			'ganalytics' => ( defined('SITE_GANALYTICS') ? SITE_GANALYTICS : false ),
			'ganalytics-tracking' => ( defined('SITE_GANALYTICS_ID') ? SITE_GANALYTICS_ID : '' ),
		),
		'admin' => array(
			'username' => '',
			'password' => '',
		),
		'facebook' => array(
			'enabled' => ( defined('FBLOGIN_ENABLED') ? FBLOGIN_ENABLED : false ),
			'appid' => ( defined('FBLOGIN_APPID') ? FBLOGIN_APPID : '' ),
			'appsecret' => ( defined('FBLOGIN_APPSECRET') ? FBLOGIN_APPSECRET : '' ),
		),
		'mail' => array(
			'mailer' => ( defined('MAIL_MAILER') ? MAIL_MAILER : 'mail' ),
			'smtp-server' => ( defined('SMTP_HOST') ? SMTP_HOST : 'localhost' ),
			'smtp-port' => ( defined('SMTP_PORT') ? SMTP_PORT : '25' ),
			'smtp-security' => ( defined('SMTP_SECURE') ? SMTP_SECURE : '' ),
			'smtp-user' => ( defined('SMTP_USER') ? SMTP_USER : 'username' ),
			'smtp-password' => ( defined('SMTP_PASS') ? SMTP_PASS : 'password' ),
			'sendmail-path' => ( defined('SENDMAIL_PATH') ? SENDMAIL_PATH : '/usr/sbin/sendmail' ),
		),
		'mysql' => array(
			'mysql-host' => ( defined('MYSQL_HOST') ? MYSQL_HOST : 'localhost' ),
			'mysql-user' => ( defined('MYSQL_USER') ? MYSQL_USER : 'username' ),
			'mysql-pass' => ( defined('MYSQL_PASS') ? MYSQL_PASS : 'password' ),
			'mysql-database' => ( defined('MYSQL_DB') ? MYSQL_DB : 'database' ),
			'mysql-table-prefix' => ( defined('MYSQL_PREFIX') ? MYSQL_PREFIX : 'lus_' ),
		),
	);
	
	$messages = array();
	
	// Make sure MySQLi is installed
	if (!extension_loaded('mysqli')) {
		$messages[] = 'PHP extension MySQLi is not installed.';
	}
	
	// Make sure GD is installed
	if (!extension_loaded('gd')) {
		$messages[] = 'PHP extension GD is not installed.';
	}
	
	// Make sure Session is installed
	if (!extension_loaded('session')) {
		$messages[] = 'PHP extension Session is not installed.';
	}
	
	// Make sure JSON is installed
	if (!extension_loaded('json')) {
		$messages[] = 'PHP extension JSON is not installed.';
	}
	
	// Make sure Multibyte String is installed
	if (!extension_loaded('mbstring')) {
		$messages[] = 'PHP extension Multibyte String is not installed.';
	}
	
	// Make sure PHP version is at least v5.3
	if (version_compare(PHP_VERSION, '5.4.0') < 0) {
		$messages[] = 'You must be running at least PHP v5.4.0.';
	}
	
	// Make sure config.php is writable
	if (!is_writable('inc/config.php')) {
		$messages[] = 'The file "config.php" in the "inc" directory must be writable.';
	}
	
	// Make sure PassHash class exists
	if (!file_exists('inc/passhash.class.php')) {
		$messages[] = 'PassHash class file (inc/passhash.class.php) not found.';
	}
	
	if (count($messages) == 0 && ($_SERVER['REQUEST_METHOD'] && $_SERVER['REQUEST_METHOD'] == 'POST')) {
		// If magic quotes enabled => remove slashes
		if (get_magic_quotes_gpc()) {
			foreach ($_POST as $k => $v) {
				$_POST[$k] = stripslashes($v);
			}
		}
	
		$site_options = $_POST['site'];
		
		if (!isset($site_options['url']) 
			|| !isset($site_options['ssl-url']) 
			|| !isset($site_options['name']) 
			|| !isset($site_options['noreply-email']) 
			|| !isset($site_options['admin-email']) 
			|| !isset($site_options['shorturl-length'])) {
			$messages[] = 'One or more of the site settings is missing.';
		}
		
		$admin_options = $_POST['admin'];
		if (!isset($admin_options['username']) || !isset($admin_options['password'])) {
			$messages[] = 'Admin login is missing.';
		}
		
		$facebook_options = $_POST['facebook'];
		if (!isset($facebook_options['appid']) || !isset($facebook_options['appsecret'])) {
			$messages[] = 'Facebook settings are missing.';
		}

		$mail_options = $_POST['mail'];
		
		if (!isset($mail_options['mailer'])) {
			$messages[] = "Mail method is missing.";
		} else {
			if ($mail_options['mailer'] == 'smtp') {
				if (!isset($mail_options['smtp-server']) 
					|| !isset($mail_options['smtp-port']) 
					|| !isset($mail_options['smtp-security']) 
					|| !isset($mail_options['smtp-user']) 
					|| !isset($mail_options['smtp-password'])) {
					$messages[] = "One or more of the SMTP settings is missing.";
				}
			} else if ($mail_options['mailer'] == 'sendmail') {
				if (!isset($mail_options['sendmail-path'])) {
					$messages[] = "Sendmail path is missing.";
				}
			}
		}
		
		$mysql_options = $_POST['mysql'];
		
		if (!isset($mysql_options['mysql-host']) 
			|| !isset($mysql_options['mysql-user']) 
			|| !isset($mysql_options['mysql-pass']) 
			|| !isset($mysql_options['mysql-database']) 
			|| !isset($mysql_options['mysql-table-prefix'])) {
			$messages[] = "One or more mysql setting is missing.";
		}
		
		if (count($messages) == 0) {
			// Validate site settings
			$site_options['url'] = trim($site_options['url']);
			$site_options['ssl-url'] = trim($site_options['ssl-url']);
			$site_options['name'] = trim($site_options['name']);
			$site_options['noreply-email'] = strtolower(trim($site_options['noreply-email']));
			$site_options['admin-email'] = strtolower(trim($site_options['admin-email']));
			$site_options['shorturl-length'] = trim($site_options['shorturl-length']);
			
			if (!filter_var($site_options['url'], FILTER_VALIDATE_URL)) {
				$messages[] = "Site URL is invalid.";
			}
			
			if (!filter_var($site_options['ssl-url'], FILTER_VALIDATE_URL)) {
				$messages[] = "Site SSL URL is invalid.";
			}
			
			if (empty($site_options['name'])) {
				$messages[] = "Site name cannot be empty.";
			}
			
			if (!filter_var($site_options['noreply-email'], FILTER_VALIDATE_EMAIL)) {
				$messages[] = "No reply email address is invalid.";
			}
			
			if (!filter_var($site_options['admin-email'], FILTER_VALIDATE_EMAIL)) {
				$messages[] = "Admin email address is invalid.";
			}
			
			if (!is_numeric($site_options['shorturl-length'])) {
				$messages[] = "Short URL length must be number.";
			} else if ($site_options['shorturl-length'] <= 0 || $site_options['shorturl-length'] > 100) {
				$messages[] = "Short URL length must be between 1-100.";
			}
			
			if (!isset($site_options['validate-ip']))
				$site_options['validate-ip'] = false;
			else
				$site_options['validate-ip'] = true;
				
			if (!isset($site_options['ganalytics']))
				$site_options['ganalytics'] = false;
			else
				$site_options['ganalytics'] = true;
				
			if ($site_options['ganalytics'] && trim($site_options['ganalytics-tracking']) == '')
				$messages[] = "Google Analytics cannot be enabled and the tracking ID be empty.";
				
			// Validate admin login
			$admin_options['username'] = trim(strtolower($admin_options['username']));
			$admin_options['password'] = trim($admin_options['password']);
			
			if (empty($admin_options['username']) || empty($admin_options['password'])) {
				$messages[] = "Admin login and password cannot be empty.";
			} else {
				if (strlen($admin_options['username']) < 3 || strlen($admin_options['username']) > 16)
					$messages[] = "Admin username must be no less than 3 characters and no greater than 16 characters";
			
				if (!preg_match('/^[a-z0-9_-]{3,}$/i', $admin_options['username']))
					$messages[] = "Admin username can only contain alphanumeric characters as well as hyphens and underscores";
					
				// Test password strength
				if (!preg_match('@[A-Z]@', $admin_options['password']) || !preg_match('@[a-z]@', $admin_options['password']) || !preg_match('@[0-9]@', $admin_options['password'])) {
					$messages[] = 'Admin password must contain at least one uppercase (A-Z), lowercase (a-z) and number (0-9)';
				}
				
				// Make sure there's no white spaces (ie: tabs, spaces)
				if (preg_match('/\s+/', $admin_options['password'])) {
					$messages[] = 'Admin password cannot contain white spaces (spaces, tabs)';
				}
				
				// Make sure password is more than 8 characters
				if (strlen($admin_options['password']) < 8) {
					$messages[] = 'Admin password must be at least eight characters long';
				}
			}
				
			// Validate Facebook settings
			if (!isset($facebook_options['enabled'])) {
				$facebook_options['enabled'] = false;
			} else {
				$facebook_options['enabled'] = true;
				
				$facebook_options['appid'] = trim($facebook_options['appid']);
				$facebook_options['appsecret'] = trim($facebook_options['appsecret']);
				
				if (empty($facebook_options['appid']) || empty($facebook_options['appid'])) {
					$messages[] = "Facebook App ID and App Secret cannot be empty.";
				}
			}
				
			// Validate mail settings
			if ($mail_options['mailer'] != 'mail' && $mail_options['mailer'] != 'smtp' && $mail_options['mailer'] != 'sendmail') {
				$messages[] = "Mail method must be either mail, smtp or sendmail.";
			} else {
				if ($mail_options['mailer'] == 'smtp') {
					$mail_options['smtp-server'] = trim($mail_options['smtp-server']);
					$mail_options['smtp-port'] = trim($mail_options['smtp-port']);
					$mail_options['smtp-security'] = ( trim($mail_options['smtp-security']) == 'none' ? '' : trim($mail_options['smtp-security']) );
					$mail_options['smtp-user'] = trim($mail_options['smtp-user']);
					$mail_options['smtp-password'] = trim($mail_options['smtp-password']);
					
					if (empty($mail_options['smtp-server'])) {
						$messages[] = "SMTP server cannot be empty.";
					} else if (strpos($mail_options['smtp-server'], ' ') !== false) {
						$messages[] = "SMTP server cannot have spaces.";
					} else if (!filter_var($mail_options['smtp-server'], FILTER_VALIDATE_IP)) {
						// Make sure hostname resolves
						if (gethostbyname($mail_options['smtp-server']) == $mail_options['smtp-server']) {
							$messages[] = "SMTP server doesn't resolve to a IP address.";
						}
					}
					
					if (!is_numeric($mail_options['smtp-port'])) {
						$messages[] = "SMTP port must be a number";
					} else if ($mail_options['smtp-port'] <= 0 || $mail_options['smtp-port'] >= 65535) {
						$messages[] = "SMTP port must be a number between 1-65534.";
					}
					
					if ($mail_options['smtp-security'] != '' && $mail_options['smtp-security'] != 'ssl' && $mail_options['smtp-security'] != 'tls') {
						$messages[] = "SMTP Security is not valid.";
					}
					
					if (empty($mail_options['smtp-user'])) {
						$messages[] = "SMTP username cannot be empty.";
					} else if (strpos($mail_options['smtp-user'], ' ') !== false) {
						$messages[] = "SMTP username cannot have spaces.";
					}
					
					if (empty($mail_options['smtp-password'])) {
						$messages[] = "SMTP password cannot be empty.";
					} else if (strpos($mail_options['smtp-password'], ' ') !== false) {
						$messages[] = "SMTP password cannot have spaces.";
					}
				} else if ($mail_options['mailer'] == 'sendmail') {
					$mail_options['sendmail-path'] = trim($mail_options['sendmail-path']);
					
					if (empty($mail_options['sendmail-path'])) {
						$messages[] = "Sendmail path cannot be empty.";
					}
				}
			}
			
			// Validate MySQL settings
			$mysql_options['mysql-host'] = trim($mysql_options['mysql-host']);
			$mysql_options['mysql-user'] = trim($mysql_options['mysql-user']);
			$mysql_options['mysql-pass'] = trim($mysql_options['mysql-pass']);
			$mysql_options['mysql-database'] = trim($mysql_options['mysql-database']);
			$mysql_options['mysql-table-prefix'] = trim($mysql_options['mysql-table-prefix']);
			
			if (empty($mysql_options['mysql-host'])) {
				$messages[] = "MySQL hostname cannot be empty.";
			}
			
			if (empty($mysql_options['mysql-user'])) {
				$messages[] = "MySQL username cannot be empty.";
			}
			
			if (empty($mysql_options['mysql-pass'])) {
				$messages[] = "MySQL password cannot be empty.";
			}
			
			if (empty($mysql_options['mysql-database'])) {
				$messages[] = "MySQL database cannot be empty.";
			}
			
			if (strpos($mysql_options['mysql-table-prefix'], ' ') !== false) {
				$messages[] = "MySQL table prefix cannot have spaces.";
			}
			
			
			// Attempt connection to MySQL
			$mysqli = @mysqli_connect($mysql_options['mysql-host'], $mysql_options['mysql-user'], $mysql_options['mysql-pass'], $mysql_options['mysql-database']);
			
			if (!$mysqli) {
				$messages[] = 'MySQL connect error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error();
			}
			
			if (count($messages) == 0) {
				function prepare_value(&$val) {
					// Escape options that are strings so config.php doesn't break
					if (is_string($val)) {
						$val = str_replace("'", "\'", $val);
					} elseif (is_bool($val)) {
						if ($val === false)
							$val = 'false';
						else
							$val = 'true';
					} else {
						$val = strval($val);
					}
				}
				
				$site_options_strings = $site_options;
				$admin_options_strings = $admin_options;
				$facebook_options_strings = $facebook_options;
				$mail_options_strings = $mail_options;
				$mysql_options_strings = $mysql_options;
				
				array_walk($site_options_strings, 'prepare_value');
				array_walk($admin_options_strings, 'prepare_value');
				array_walk($facebook_options_strings, 'prepare_value');
				array_walk($mail_options_strings, 'prepare_value');
				array_walk($mysql_options_strings, 'prepare_value');

				// We are go for installation!
				
				$config_text = <<<PHP
<?php
// Website config
define('SITE_URL', '{$site_options_strings['url']}');
define('SITE_SSLURL', '{$site_options_strings['ssl-url']}');
define('SITE_NAME', '{$site_options_strings['name']}');
define('SITE_NOREPLY', '{$site_options_strings['noreply-email']}');
define('SITE_ADMINEMAIL', '{$site_options_strings['admin-email']}');
define('SITE_SHORTURLLENGTH', {$site_options_strings['shorturl-length']});
define('SITE_VALIDATEIP', {$site_options_strings['validate-ip']}); // If true, sessions are locked to one IP address

define('SITE_GANALYTICS', {$site_options_strings['ganalytics']}); // Set to true to enable Google Analytics tracking
define('SITE_GANALYTICS_ID', '{$site_options_strings['ganalytics-tracking']}'); // Google Analytics tracking ID (usually something like UA-12345678-12)

// Facebook login
define('FBLOGIN_ENABLED', {$facebook_options_strings['enabled']}); // Set to true to allow Facebook login
define('FBLOGIN_APPID', '{$facebook_options_strings['appid']}'); // Facebook App ID
define('FBLOGIN_APPSECRET', '{$facebook_options_strings['appsecret']}'); // Facebook App Secret

// Mailer to use (Can be mail, smtp, or sendmail)
// If using SMTP, or sendmail be sure to configure it properly below
define('MAIL_MAILER', '{$mail_options_strings['mailer']}');

// SMTP server info
define('SMTP_HOST', '{$mail_options_strings['smtp-server']}');
define('SMTP_PORT', {$mail_options_strings['smtp-port']});
define('SMTP_SECURE', '{$mail_options_strings['smtp-security']}'); // Can be ssl, tls or blank for none
define('SMTP_USER', '{$mail_options_strings['smtp-user']}');
define('SMTP_PASS', '{$mail_options_strings['smtp-password']}');

// Sendmail path
define('SENDMAIL_PATH', '{$mail_options_strings['sendmail-path']}');

// MySQL config
define('MYSQL_HOST', '{$mysql_options_strings['mysql-host']}');
define('MYSQL_USER', '{$mysql_options_strings['mysql-user']}');
define('MYSQL_PASS', '{$mysql_options_strings['mysql-pass']}');
define('MYSQL_DB', '{$mysql_options_strings['mysql-database']}');
define('MYSQL_PREFIX', '{$mysql_options_strings['mysql-table-prefix']}');
PHP;

				if (file_put_contents('inc/config.php', $config_text) === false) {
					$messages[] = 'Error writing to "inc/config.php".';
				} else {
					$sql = <<<SQL
CREATE TABLE IF NOT EXISTS `{$mysql_options_strings['mysql-table-prefix']}urls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `short_url` varchar(8) NOT NULL,
  `long_url` text NOT NULL,
  `user` int(11) NOT NULL,
  `visits` int(11) NOT NULL,
  PRIMARY KEY (`short_url`),
  KEY `id` (`id`)
);

CREATE TABLE IF NOT EXISTS `{$mysql_options_strings['mysql-table-prefix']}users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `birthday` date NOT NULL,
  `password` varchar(83) NOT NULL,
  `api_key` varchar(33) NOT NULL,
  `new_email_key` varchar(33) NOT NULL,
  `new_email` varchar(255) NOT NULL,
  `reset_password_key` varchar(33) NOT NULL,
  `activate_key` varchar(33) NOT NULL,
  PRIMARY KEY (`email`),
  KEY `id` (`id`)
);

CREATE TABLE IF NOT EXISTS `{$mysql_options_strings['mysql-table-prefix']}admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(83) NOT NULL,
  PRIMARY KEY (`username`),
  KEY `id` (`id`)
);
SQL;
					if (mysqli_multi_query($mysqli, $sql) === false) {
						$messages[] = "Error creating tables in MySQL database.";
					} else {
						// Must be called before any other statements can be executed
						
						while(mysqli_next_result($mysqli)) {
							if($result = mysqli_store_result($mysqli)) {
								$result->free();
							}

						}
					
						// Hash admin password
						require_once('inc/passhash.class.php');
						$admin_pass_hash = PassHash::hash($admin_options['password']);
						
						// Add admin to database
						if ($stmt = mysqli_prepare($mysqli, 'INSERT INTO '.$mysql_options_strings['mysql-table-prefix'].'admins (`username`,`password`) VALUES(?,?)')) {
							$stmt->bind_param('ss', $admin_options['username'], $admin_pass_hash);
							
							if (!$stmt->execute())
								$messages[] = 'Unable to insert admin user. There was an error executing the statement.';
								
							$stmt->close();
						} else {
							$messages[] = 'Unable to insert admin user. There was an error preparing the statement. (Error: '.mysqli_error($mysqli).')';
						}
					
						if (empty($messages))
							$success_message = "Little URL Shortener has been installed. Please remove or rename install.php.";
					}
				
					$mysqli->close();
				}

				
			}
		}
		
		$default_values = array_merge($default_values, array('site' => $site_options, 'admin' => $admin_options, 'facebook' => $facebook_options, 'mail' => $mail_options, 'mysql' => $mysql_options));
	}
?>
<html>
	<head>
		<title>Little URL Shortener Install</title>
		
		<script type="text/javascript" src="js/jquery.min.js"></script>
		<script type="text/javascript">
			$(document).ready(function() {
				$("#mailer").change(function() {
					mailer = $("#mailer option:selected").val();
					
					$("#smtp-options:visible,#sendmail-options:visible").slideUp();
					
					if (mailer == "smtp") {
						$("#smtp-options").slideDown();
					} else if (mailer == "sendmail") {
						$("#sendmail-options").slideDown();
					}
				});
				
				$("#mailer").change();
			});
		</script>
		<link href='http://fonts.googleapis.com/css?family=Montserrat:400,700' rel='stylesheet' type='text/css' />
		<style type="text/css">
			html, body, div, span, applet, object, iframe,
			h1, h2, h3, h4, h5, h6, p, blockquote, pre,
			a, abbr, acronym, address, big, cite, code,
			del, dfn, em, img, ins, kbd, q, s, samp,
			small, strike, strong, sub, sup, tt, var,
			b, u, i, center,
			dl, dt, dd, ol, ul, li,
			fieldset, form, label, legend,
			table, caption, tbody, tfoot, thead, tr, th, td,
			article, aside, canvas, details, embed, 
			figure, figcaption, footer, header, hgroup, 
			menu, nav, output, ruby, section, summary,
			time, mark, audio, video {
				margin: 0;
				padding: 0;
				border: 0;
				font-size: 100%;
				font: inherit;
				vertical-align: baseline;
			}
			/* HTML5 display-role reset for older browsers */
			article, aside, details, figcaption, figure, 
			footer, header, hgroup, menu, nav, section {
				display: block;
			}
			body {
				line-height: 1;
				background-color: #232323;
			}
			ol, ul {
				list-style: none;
			}
			blockquote, q {
				quotes: none;
			}
			blockquote:before, blockquote:after,
			q:before, q:after {
				content: '';
				content: none;
			}
			table {
				border-collapse: collapse;
				border-spacing: 0;
			}

			body {
				background-color: #fff;
				color: #000;
				
			}
			
			.main {
				
			}
			
			.main > p {
				font: 700 14px 'Montserrat', sans-serif;
				text-align: center;
				margin: 20px 0;
			}
			
			.main > form > div {
				background-color: #EBE7DF;
				border: 3px solid #1F7F5C;
				width: 959px;
				margin: 0 auto 17px;
			}
			
			.main > form > div > p {
				font: 700 14px 'Montserrat', sans-serif;
				text-align: center;
				margin: 20px 0;
			}
			
			.main > form > div > ul li {
				font: 14px 'Montserrat', sans-serif;
				margin: 10px auto;
				clear: both;
				height: 23px; 
				width: 480px; 
			}
			
			.main > form > div > ul li > label {
				display: block;
				float: left;
				width: 215px;
				text-align: right;
				padding-right: 10px;
			}
			
			.main > form > div > ul li > input[type="text"], .main > form > div > ul li > input[type="password"], .main > form > div > ul li > select {
				float: left;
				width: 255px;
				font: inherit;
			}
			
			.main > form > div > input[type="submit"] {
				background-color: #1F7F5C;
				border: 0 none;
				color: #FFFFFF;
				cursor: pointer;
				display: block;
				font: 18px 'Montserrat',sans-serif;
				height: 61px;
				margin: 10px auto;
				text-align: center;
				width: 112px;
			}
			
			.errors {
				width: 959px;
				background: url("images/warning.png") no-repeat scroll 247px 13px #FBE3E4;
				margin: 15px auto;
				border: 3px solid #8A1F11;
			}
			
			.errors > p {
				font: 700 18px/61px 'Montserrat',sans-serif;
				text-align: center;
				color: #8A1F11;
			}
			
			.errors > ul > li {
				font: 18px/61px 'Montserrat',sans-serif;
				text-align: center;
			}
			
			.success {
				width: 959px;
				margin: 15px auto; 
				text-align: center; 
				background-color: #1F7F5C; 
				font: 700 18px/61px 'Montserrat',sans-serif;
				color: #fff;
			}
		</style>
	</head>
	<body>
		<?php if (count($messages) > 0) : ?>
		<div class="errors">
			<p>Attention! Please correct the errors below.</p>
			<ul>
				<?php foreach ($messages as $message) : ?>
				<li><?php echo $message ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php endif; ?>
		<?php if (isset($success_message)) : ?>
			<div class="success"><?php echo $success_message ?></div>
		<?php endif; ?>
		<div class="main">
			<p>Install Little URL Shortener</p>
			<form action="install.php" method="post">
				<div class="site">
					<p>Site Configuration</p>
					<ul>
						<li><label for="url">URL: </label><input type="text" name="site[url]" id="url" value="<?php echo $default_values['site']['url'] ?>" /></li>
						<li><label for="ssl-url">SSL URL: </label><input type="text" name="site[ssl-url]" id="ssl-url" value="<?php echo $default_values['site']['ssl-url'] ?>" /></li>
						<li><label for="name">Site Name: </label><input type="text" name="site[name]" id="name" value="<?php echo $default_values['site']['name'] ?>" /></li>
						<li><label for="noreply-email">No Reply Email: </label><input type="text" name="site[noreply-email]" id="noreply" value="<?php echo $default_values['site']['noreply-email'] ?>" /></li>
						<li><label for="admin-email">Admin Email: </label><input type="text" name="site[admin-email]" id="admin-email" value="<?php echo $default_values['site']['admin-email'] ?>" /></li>
						<li><label for="shorturl-length">Short URL Length: </label><input type="text" name="site[shorturl-length]" id="shorturl-length" style="width: 50px" value="<?php echo $default_values['site']['shorturl-length'] ?>" /></li>
						<li><label for="validate-ip">Validate IP?</label><input type="checkbox" name="site[validate-ip]" id="validate-ip" <?php echo ( $default_values['site']['validate-ip'] == true ? 'checked' : '' ) ?> /></li>
						<li><label for="validate-ip">Use Google Analytics?</label><input type="checkbox" name="site[ganalytics]" id="ganalytics" <?php echo ( $default_values['site']['ganalytics'] == true ? 'checked' : '' ) ?> /></li>
						<li><label for="name">Google Analytics Tracking ID: </label><input type="text" name="site[ganalytics-tracking]" id="ganalytics-tracking" value="<?php echo $default_values['site']['ganalytics-tracking'] ?>" /></li>
						
					</ul>
				</div>
				<div class="admin">
					<p>Admin Configuration</p>
					<ul>
						<li><label for="admin-username">Username: </label><input type="text" name="admin[username]" id="admin-username" value="<?php echo $default_values['admin']['username'] ?>" /></li>
						<li><label for="admin-password">Password: </label><input type="password" name="admin[password]" id="admin-password" value="<?php echo $default_values['admin']['password'] ?>" /></li>
					</ul>
				</div>
				<div class="facebook">
					<p>Facebook Configuration</p>
					<ul>
						<li><label for="facebook-enabled">Enable Facebook Login?</label><input type="checkbox" name="facebook[enabled]" id="facebook-enabled" <?php echo ( $default_values['facebook']['enabled'] == true ? 'checked' : '' ) ?> /></li>
						<li><label for="facebook-appid">App ID: </label><input type="text" name="facebook[appid]" id="facebook-appid" value="<?php echo $default_values['facebook']['appid'] ?>" /></li>
						<li><label for="facebook-appsecret">App Secret: </label><input type="text" name="facebook[appsecret]" id="facebook-appsecret" value="<?php echo $default_values['facebook']['appsecret'] ?>" /></li>
					</ul>
				</div>
				<div class="mail">
					<p>Mail Configuration</p>
					<ul>
						<li>
							<label for="mailer">Mailer: </label>
							<select name="mail[mailer]" id="mailer">
								<option value="mail" <?php echo ( $default_values['mail']['mailer'] == 'mail' ? 'selected' : '' ) ?>>PHP Mail()</option>
								<option value="smtp" <?php echo ( $default_values['mail']['mailer'] == 'smtp' ? 'selected' : '' ) ?>>SMTP</option>
								<option value="sendmail" <?php echo ( $default_values['mail']['mailer'] == 'sendmail' ? 'selected' : '' ) ?>>Sendmail</option>
							</select>
						</li>
						<div id="smtp-options" style="display: none">
							<li><label for="smtp-server">SMTP Server: </label><input type="text" name="mail[smtp-server]" id="smtp-server" value="<?php echo $default_values['mail']['smtp-server'] ?>" /></li>
							<li><label for="smtp-port">SMTP Port: </label><input type="text" name="mail[smtp-port]" id="smtp-port" value="<?php echo $default_values['mail']['smtp-port'] ?>" /></li>
							<li>
								<label for="smtp-security">SMTP Security: </label>
								<select name="mail[smtp-security]" id="smtp-security">
									<option value="none" <?php echo ( $default_values['mail']['smtp-security'] == '' ? 'selected' : '' ) ?>>None</option>
									<option value="ssl" <?php echo ( $default_values['mail']['smtp-security'] == 'ssl' ? 'selected' : '' ) ?>>SSL</option>
									<option value="tls" <?php echo ( $default_values['mail']['smtp-security'] == 'tls' ? 'selected' : '' ) ?>>TLS</option>
								</select>
							</li>
							<li><label for="smtp-user">SMTP User: </label><input type="text" name="mail[smtp-user]" id="smtp-user" value="<?php echo $default_values['mail']['smtp-user'] ?>" /></li>
							<li><label for="smtp-password">SMTP Password: </label><input type="text" name="mail[smtp-password]" id="smtp-password" value="<?php echo $default_values['mail']['smtp-password'] ?>" /></li>
						</div>
						<div id="sendmail-options" style="display: none">
							<li><label for="sendmail-path">Sendmail Path: </label><input type="text" name="mail[sendmail-path]" id="sendmail-path" value="<?php echo $default_values['mail']['sendmail-path'] ?>" /></li>
						</div>
					</ul>
				</div>
				<div class="mysql">
					<p>MySQL Configuration</p>
					<ul>
						<li><label for="mysql-host">MySQL Hostname: </label><input type="text" name="mysql[mysql-host]" id="mysql-host" value="<?php echo $default_values['mysql']['mysql-host'] ?>" /></li>
						<li><label for="mysql-user">MySQL User: </label><input type="text" name="mysql[mysql-user]" id="mysql-user" value="<?php echo $default_values['mysql']['mysql-user'] ?>" /></li>
						<li><label for="mysql-pass">MySQL Password: </label><input type="text" name="mysql[mysql-pass]" id="mysql-pass" value="<?php echo $default_values['mysql']['mysql-pass'] ?>" /></li>
						<li><label for="mysql-database">MySQL Database: </label><input type="text" name="mysql[mysql-database]" id="mysql-database" value="<?php echo $default_values['mysql']['mysql-database'] ?>" /></li>
						<li><label for="mysql-table-prefix">Table Prefix: </label><input type="text" name="mysql[mysql-table-prefix]" id="mysql-table-prefix" value="<?php echo $default_values['mysql']['mysql-table-prefix'] ?>" /></li>
					</ul>
				</div>
				<div class="install">
					<p>Click the button below to install Little URL Shortener</p>
					<input type="submit" name="submit" value="Install" />
				</div>
			</form>
		</div>
	</body>
</html>