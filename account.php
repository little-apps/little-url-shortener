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

	require_once('inc/main.php');
	require_once('inc/passhash.class.php');
	
	if ((isset($_GET['action']) && isset($_GET['key'])) && $_GET['action'] == 'updateEmail') {
		// Store key in session (in case user needs to login)
		$_SESSION['update_email_key'] = $_GET['key'];
	}
	
	if ($logged_in == false) {
		redirect('login.php');
	}
	
	$success_messages = array();
	
	// Get user info
	$stmt = $mysqli->prepare("SELECT first_name, last_name, email, password, api_key FROM `".MYSQL_PREFIX."users` WHERE id = ? LIMIT 0,1");
	$stmt->bind_param('s', $_SESSION['user_id']);
	$stmt->execute();
	
	$first_name = '';
	$last_name = '';
	$user_email = '';
	$pass_hash = '';
	$api_key = '';
	
	$stmt->bind_result($first_name, $last_name, $user_email, $pass_hash, $api_key);

	if ($stmt->fetch() !== true) {
		redirect('login.php');
	}
	
	$stmt->close();
	
	// Get URLs
	$urls = array();
	
	$stmt = $mysqli->prepare("SELECT id, short_url, long_url, visits FROM `".MYSQL_PREFIX."urls` WHERE user = ?");
	$stmt->bind_param('i', $_SESSION['user_id']);
	$stmt->execute();
	
	$stmt->bind_result($url_id, $short_url, $long_url, $url_visits);
	
	while ($stmt->fetch()) {
		$urls[] = array('id' => $url_id, 'short_url' => ( substr_compare($long_url, 'https', 0, 5, true) == 0 ? SITE_SSLURL : SITE_URL ) .'/'. $short_url, 'long_url' => $long_url, 'visits' => $url_visits);
	}
	
	$stmt->close();
	
	if (isset($_SESSION['update_email_key'])) {
		// Look for key
		$stmt = $mysqli->prepare("SELECT new_email FROM `".MYSQL_PREFIX."users` WHERE id = ? AND new_email_key = ? LIMIT 0,1");
		$stmt->bind_param('is', $_SESSION['user_id'], $_SESSION['update_email_key']);
		$stmt->execute();
		
		$stmt->bind_result($new_email);
		
		if ($stmt->fetch() !== true) {
			$messages[] = "The key entered was not found.";
			
			$stmt->close();
		} else {
			$stmt->close();
		
			$stmt = $mysqli->prepare("UPDATE `".MYSQL_PREFIX."users` SET email=?,new_email_key='' WHERE id=?");
			$stmt->bind_param('si', $new_email, $_SESSION['user_id']);
			$stmt->execute();
			$stmt->close();
			
			// Send email
			$subject = "Your Account Has Been Updated";
			
			$message = "This e-mail is to notify you that your e-mail address has been updated. Below is your new account information:" . PHP_EOL . PHP_EOL;
			$message .= "E-mail address: " . $new_email . PHP_EOL;
			$message .= "You can login to your account by visiting ". SITE_URL . "/login.php." . PHP_EOL;
			$message .= "Please keep this e-mail for your records." . PHP_EOL . PHP_EOL;
			$message .= "This is an automated response, please do not reply!";
			
			send_email($user_email, $first_name, SITE_NOREPLY, '', $subject, $message);
		
			// Notify
			$success_messages[] = 'Your e-mail address has been updated.';
			
			// Refresh variable
			$user_email = $new_email;
			
			// Update session + cookie hash
			$_SESSION['user_hash'] = md5($_SESSION['user_id'].$user_email.$pass_hash);
			
			if (isset($_COOKIE['7LSNETHASH'])) {
				setcookie( "7LSNETHASH", $_SESSION['user_hash'], time() + 60 * 60 * 24 * 365 );
			}
		}
		
		// Remove session variable
		unset($_SESSION['update_email_key']);
	} else if ($csrf_valid == true && isset($_POST['action'])) {
	
		if ($_POST['action'] == 'update') {
			$current_pass = ( (isset($_POST['currentpass'])) ? trim($_POST['currentpass']) : '' );
			
			if (PassHash::check_password($pass_hash, $current_pass)) {
				$new_first_name = ( (isset($_POST['first'])) ? trim($_POST['first']) : '' );
				$new_last_name = ( (isset($_POST['last'])) ? trim($_POST['last']) : '' );
				$new_email = ( (isset($_POST['email'])) ? trim(strtolower($_POST['email'])) : '' );
				$new_password = ( (isset($_POST['pass1'])) ? trim($_POST['pass1']) : '' );
				$new_password_confirm = ( (isset($_POST['pass2'])) ? trim($_POST['pass2']) : '' );
				
				// Validate info
				if (!preg_match('/^[a-z\s]+$/i', $new_first_name)) {
					$messages[] = 'First name is invalid';
				}
				
				if (!preg_match('/^[a-z\s]+$/i', $new_last_name)) {
					$messages[] = 'Last name is invalid';
				}
				
				if ($new_email == '') {
					$messages[] = 'E-mail cannot be empty';
				}
				
				if ($new_email != $user_email) {
					// Check if email already registered
					if ($result = $mysqli->query("SELECT * FROM `".MYSQL_PREFIX."users` WHERE `email` = '".$mysqli->escape_string($new_email)."'")) {
						if ($result->num_rows > 0)
							$messages[] = 'Email address is already registered';
					}
					
					// Valid e-mail
					if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
						$messages[] = 'Email is invalid';
					}
				}
				
				if ($new_password != '') {
					if ($new_password != $new_password_confirm) {
						$messages[] = 'Passwords do not match';
					}
				
					// Test password strength
					if (!preg_match('@[A-Z]@', $new_password) || !preg_match('@[a-z]@', $new_password) || !preg_match('@[0-9]@', $new_password)) {
						$messages[] = 'Password must contain at least one uppercase (A-Z), lowercase (a-z) and number (0-9)';
					}
					
					// Make sure there's no white spaces (ie: tabs, spaces)
					if (preg_match('/\s+/', $new_password)) {
						$messages[] = 'Password cannot contain white spaces (spaces, tabs)';
					}
					
					// Make sure password is more than 8 characters
					if (strlen($new_password) < 8) {
						$messages[] = 'Password must be at least eight characters long';
					}
				}
				
				if ($new_first_name == $first_name && $new_last_name == $last_name && $new_email == $user_email && $new_password == '') {
					$success_messages[] = 'Nothing needs to be updated.';
				} else if (count($messages) == 0) {
					if ($user_email != $new_email) {
						// Create activation key for email change
						$rand_key = md5(uniqid('newemail_'));
						
						$stmt = $mysqli->prepare("UPDATE `".MYSQL_PREFIX."users` SET new_email_key = ?,new_email = ? WHERE id=?");
						$stmt->bind_param('ssi', $rand_key, $new_email, $_SESSION['user_id']);
						$stmt->execute();
						$stmt->close();
					
						// Send email
						$subject = "Action Required";
						
						$message = "This message is to alert you that someone is trying to change your e-mail address." . PHP_EOL;
						$message .= "If you would like to update the e-mail address, then please click the link below. Otherwise, please ignore this message." . PHP_EOL . PHP_EOL;
						$message .= "<" . SITE_URL . "/account.php?action=updateEmail&key=".$rand_key.">" . PHP_EOL . PHP_EOL;
						$message .= "This is an automated response, please do not reply!";
						
						send_email($user_email, $first_name, SITE_NOREPLY, '', $subject, $message);
						
						// Notify
						$success_messages[] = 'Verification is required to update your e-mail address.';
					} 
					
					if ($new_first_name != $first_name || $new_last_name != $last_name || $new_password != '') {
						$new_pass_hash = $pass_hash;
					
						if ($new_password != '') {
							// Generate new password
							$new_pass_hash = PassHash::hash($new_password);
						}
						
						$stmt = $mysqli->prepare("UPDATE `".MYSQL_PREFIX."users` SET first_name=?,last_name=?,password=? WHERE id=?");
						$stmt->bind_param('sssi', $new_first_name, $new_last_name, $new_pass_hash, $_SESSION['user_id']);
						$stmt->execute();
						$stmt->close();
						
						// Notify email of changes
						$subject = "Your Account Has Been Updated";
						
						$message = "This e-mail is to notify you that your account has been updated. Below is your new account information:" . PHP_EOL . PHP_EOL;
						$message .= "First name: " . $new_first_name . PHP_EOL;
						$message .= "Last name:" . $new_last_name . PHP_EOL;
						$message .= "Password: " . ( ($new_password != '') ? '(changed)' : '(unchanged)' ) . PHP_EOL . PHP_EOL;
						$message .= "You can login to your account by visiting " . SITE_URL . "/login.php." . PHP_EOL;
						if ($new_password != '') $message .= "If you didn't change your password, then you can reset it by visiting ".SITE_URL."/forgot-password.php." . PHP_EOL;
						$message .= "Please keep this e-mail for your records." . PHP_EOL;
						$message .= "This is an automated response, please do not reply!";
						
						send_email($user_email, $first_name, SITE_NOREPLY, '', $subject, $message);
					
						// Refresh variables
						$first_name = $new_first_name;
						$last_name = $new_last_name;
						
						// If password changed -> update hash
						if ($new_password != '') {
							$_SESSION['user_hash'] = md5($_SESSION['user_id'].$user_email.$new_pass_hash);
			
							if (isset($_COOKIE['7LSNETHASH'])) {
								setcookie( "7LSNETHASH", $_SESSION['user_hash'], time() + 60 * 60 * 24 * 365 );
							}
						}
						
						// Notify
						$success_messages[] = 'Your account information has been updated.';
					}
				}
				
				
			} else {
				$messages[] = 'Wrong password entered';
			}
		} else if ($_POST['action'] == 'regenerate') {
			// Generate new API key
			$api_key = md5(uniqid('api_'));
			
			$stmt = $mysqli->prepare("UPDATE `".MYSQL_PREFIX."users` SET api_key=? WHERE id=?");
			$stmt->bind_param('si', $api_key, $_SESSION['user_id']);
			$stmt->execute();
			$stmt->close();
			
			// Notify
			$success_messages[] = 'Your API key has been regenerated.';
		} else if ($_POST['action'] == 'delete') {
			if ((!isset($_POST['ids'])) || !is_array($_POST)) {
				$messages[] = 'No rows were selected';
			} else {
				// Build query
				$sql = "DELETE FROM `".MYSQL_PREFIX."urls` WHERE user=? AND(";
				
				foreach ($_POST['ids'] as $id) {
					$sql .= "id = ".intval($id)." OR ";
				}
				
				$sql = substr($sql, 0, -4) . ")";
				
				// Remove rows
				$stmt = $mysqli->prepare($sql);
				$stmt->bind_param('i', $_SESSION['user_id']);
				$stmt->execute();
				$stmt->close();
			
				// Refresh table
				$urls = array();
	
				$stmt = $mysqli->prepare("SELECT id, short_url, long_url, visits FROM `".MYSQL_PREFIX."urls` WHERE user = ?");
				$stmt->bind_param('i', $_SESSION['user_id']);
				$stmt->execute();
				
				$stmt->bind_result($url_id, $short_url, $long_url, $url_visits);
				
				while ($stmt->fetch()) {
					$urls[] = array('id' => $url_id, 'short_url' => ( substr_compare($long_url, 'https', 0, 5, true) == 0 ? SITE_SSLURL : SITE_URL ) .'/'. $short_url, 'long_url' => $long_url, 'visits' => $url_visits);
				}
				
				$stmt->close();
				
				// Notify user
				$success_messages[] = 'Selected URLs have been deleted.';
			}
		}
	}
?>
<html>
	<head>
		<title><?php title('Account'); ?></title>
		<?php meta_tags(); ?>
		
		<link href='http://fonts.googleapis.com/css?family=Montserrat:400,700' rel='stylesheet' type='text/css' />
		<link rel="stylesheet" type="text/css" href="style.css" media="screen">
		<script src="js/jquery.min.js" type="text/javascript"></script>
		<script src="js/jquery.dataTables.min.js" type="text/javascript"></script>
		<script src="js/jquery.zclip.min.js" type="text/javascript"></script>
		<script src="js/main.js" type="text/javascript"></script>
		
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$('#datatable').dataTable({"bInfo": false, "bLengthChange": false, "sPaginationType": "full_numbers", "iDisplayLength": 50, "oLanguage": { "oPaginate": { "sFirst": "&laquo;&laquo;", "sPrevious": "&laquo;", "sNext": "&raquo;", "sLast": "&raquo;&raquo;" }}});
				
				$('button#delete').click(function() {
					if ($('#datatable > tbody > tr > td#on').length > 0) {
						if (confirm('Are you sure?')) {
							$form = $('<form action="account.php" method="post" />');
							$form.append('<input type="hidden" name="token" value="<?php echo $csrf_token; ?>" />');
							$form.append('<input type="hidden" name="action" value="delete" />');
							
							$('#datatable > tbody > tr > td#on').each(function() {
								$form.append('<input type="hidden" name="ids[]" value="'+$(this).parent().attr('id')+'" />');
							});
							
							$('body').append($form);
							$form.submit();
						}
					} else {
						alert('No rows are selected');
					}
				});
			});
		</script>
	</head>
	<body>
		<?php ganalytics_tracking(); ?>
		<?php output_errors(); ?>
		<?php output_short_url(); ?>
		<div id="topmenu">
			<div id="nav">
				<div id="box">
					<ul>
						<li><a href="/">Home</a></li>
						<li><a href="account.php">Account</a></li>
						<li><a href="api-docs.php">API Documentation</a></li>
						<li><a href="contact.php">Contact</a></li>
					</ul>
				</div>
				<div id="login">
					<div id="logout"><a href="login.php?action=logout">Sign Out</a></div>
				</div>
			</div>
		</div>
		<div id="wrapper">
			<div id="top">
				<div class="form">
					<form action="#" method="post">
						<input type="hidden" name="token" value="<?php echo $csrf_token ?>" />
						<input type="text" name="url" id="url" placeholder="http://" value="" />
						<input type="submit" name="submit" id="submit" value="Shorten URL" />
					</form>
				</div>
			</div>
			<div id="bottom">
				<?php if (isset($success_messages)) : ?>
				<?php foreach ($success_messages as $success_message) : ?>
				<div id="notify"><?php echo $success_message; ?></div>
				<?php endforeach; ?>
				<?php endif; ?>
				<div id="urls">
					<p>Shortened URLs</p>
					<table id="datatable">
						<thead>
							<tr>
								<th></th>
								<th>Short URL</th>
								<th>Long URL</th>
								<th>Visits</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($urls as $url) : ?>
							<tr id="<?php echo $url['id']; ?>">
								<td id="off">&nbsp;</td>
								<td><?php echo $url['short_url']; ?></td>
								<td><?php echo $url['long_url']; ?></td>
								<td><?php echo $url['visits']; ?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<button id="delete">Delete Selected</button>
				</div>
				<div id="account">
					<p>Account Info</p>
					<form action="account.php" method="post">
						<input type="hidden" name="action" value="update" />
						<input type="hidden" name="token" value="<?php echo $csrf_token; ?>" />
						<span>(Items marked with * are optional)</span>
						<div class="clear"></div>
						<ul id="left">
							<li><label for="first">First Name</label><input type="text" name="first" id="first" value="<?php echo $first_name ?>" /></li>
							<li><label for="last">Last Name</label><input type="text" name="last" id="last" value="<?php echo $last_name ?>" /></li>
							<li><label for="email">E-mail</label><input type="text" name="email" id="email" value="<?php echo $user_email ?>" /></li>
						</ul>
						<ul id="right">
							<li><label for="pass1">New Password <span class="optional">*</span></label><input type="password" name="pass1" id="pass1" value="" /></li>
							<li><label for="pass2">Confirm <span class="optional">*</span></label><input type="password" name="pass2" id="pass2" value="" /></li>
							
						</ul>
						<div class="clear"></div>
						<ul id="bot-right">
							<li><label for="currentpass">Password</label><input type="password" name="currentpass" id="currentpass" value="" /></li>
							<li><input type="submit" name="submit" value="Update" /></li>
						</ul>
						
					</form>
				</div>
				<div id="api-key">
					<p>API Key</p>
					<div id="box">
						<form action="account.php" method="post">
							<input type="hidden" name="action" value="regenerate" />
							<input type="hidden" name="token" value="<?php echo $csrf_token; ?>" />
							<p>The API key allows you to generate shortened URLs and save them to your account. See the <a href="api-docs.php">API documentation</a> for information on how to use the API.</p>
							<input type="text" name="key" id="key" value="<?php echo $api_key ?>" readonly />
							<input type="submit" name="regen" id="regen" value="Regenerate API Key" />
						</form>
					</div>
				</div>
			</div>			
		</div>
		<div id="footer">
			<div id="copyright"><a href="https://github.com/little-apps/little-url-shortener" target="_blank">Little URL Shortener</a> was developed by <a href="http://www.little-apps.com" target="_blank">Little Apps</a> and is licensed under the <a href="http://www.gnu.org/licenses/gpl.html" target="_blank">GNU GPLv3</a>.</div>
			<div id="navigation"><a href="index.php">Home</a> | <a href="account.php">Account</a> | <a href="api-docs.php">API Documentation</a> | <a href="contact.php">Contact</a></div>
		</div>
	</body>
	
</html>