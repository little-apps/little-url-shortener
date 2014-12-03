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
	define('LUS_ADMINAREA', true);
	
	require_once('../inc/main.php');
	require_once('../inc/passhash.class.php');
	
	if ($admin_logged_in == false) {
		redirect('login.php');
	}
	
	$user_exists = false;
	
	if (!isset($_GET['id'])) {
		$error = 'No user ID specified.';
	} else if (!is_numeric($_GET['id'])) {
		$error = 'Invalid user ID specified.';
	} else {
		$user_id = intval($_GET['id']);
		
		// Does user exist?
		if ($stmt = $mysqli->prepare("SELECT first_name, last_name, email, password FROM `".MYSQL_PREFIX."users` AS users WHERE id = ? LIMIT 0,1")) {
			$stmt->bind_param('i', $user_id);
			$stmt->execute();
			
			$stmt->bind_result($first_name, $last_name, $email, $pass_hash);
			
			if (!$stmt->fetch()) {
				$error = 'User ID not found.';
			} else {
				$user_exists = true;
			}
			
			$stmt->close();
		}
		
	}
	
	if ($user_exists) {
		$urls = array();
	
		// Get URLs
		$stmt = $mysqli->prepare("SELECT id, short_url, long_url, visits FROM `".MYSQL_PREFIX."urls` WHERE user = ?");
		$stmt->bind_param('i', $user_id);
		$stmt->execute();
		
		$stmt->bind_result($url_id, $short_url, $long_url, $visits);
		
		while ($stmt->fetch()) {
			$short_url_link = ( substr_compare($long_url, 'https', 0, 5, true) == 0 ? SITE_SSLURL : SITE_URL ) .'/'. $short_url;
			$short_url_text = str_replace('http://', '', SITE_URL) . '/' . $short_url;
			
			$long_url_link = $long_url;
			$long_url_text = str_replace(array('http://', 'https://'), '', $long_url);
		
			$urls[$url_id] = array('short_url_link' => $short_url_link, 'short_url_text' => $short_url_text, 'long_url_link' => $long_url_link, 'long_url_text' => $long_url_text, 'visits' => $visits, 'user_id' => $user_id);
		}
		
		$stmt->close();
						
		
		if (isset($_SERVER['REQUEST_METHOD']) && strcasecmp($_SERVER['REQUEST_METHOD'], 'post') == 0) {
			if (!$csrf_valid) {
				$messages[] = 'Cross-site Request Forgery (CSRF) token is invalid.';
			} else if (!isset($_POST['action'])) {
				$messages[] = 'No action specified.';
			} else if (!in_array($_POST['action'], array('delete-urls', 'update-user-info'))) {
				$messages[] = 'Invalid action specified.';
			}
			
			if (empty($messages)) {
				if ($_POST['action'] == 'delete-urls') {
					if ((!isset($_POST['ids'])) || !is_array($_POST['ids'])) {
						$messages[] = 'No IDs were specified.';
					} else {
						// Build query
						$sql = "DELETE FROM `".MYSQL_PREFIX."urls` WHERE user=? AND(";
						
						foreach ($_POST['ids'] as $id) {
							if (is_numeric($id))
								$sql .= "id = ".intval($id)." OR ";
						}
						
						$sql = substr($sql, 0, -4) . ")";
						
						// Remove rows
						if ($stmt = $mysqli->prepare($sql)) {
							$stmt->bind_param('i', $user_id);
							
							if ($stmt->execute()) {
								// Remove rows from array
								foreach ($_POST['ids'] as $id) {
									if (is_numeric($id) && isset($urls[$id]))
										unset($urls[$id]);
								}
							}
							
							$stmt->close();
						
							// Success
							$success_messages[] = 'Selected URLs have been deleted.';
						} else {
							$messages[] = 'Error preparing statement to remove URLs.';
						}
					}
				} else if ($_POST['action'] == 'update-user-info') {
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
					
					if ($new_email != $email) {
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
					
					// Update password?
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
					
					if ($new_first_name == $first_name && $new_last_name == $last_name && $new_email == $email && $new_password == '') {
						$success_messages[] = 'Nothing needs to be updated.';
					} else if (empty($messages)) {
						$new_pass_hash = $pass_hash;
					
						if ($new_password != '') {
							// Generate new password
							$new_pass_hash = PassHash::hash($new_password);
						}
						
						if ($stmt = $mysqli->prepare("UPDATE `".MYSQL_PREFIX."users` SET first_name=?,last_name=?,email=?,password=? WHERE id=?")) {
							$stmt->bind_param('ssssi', $new_first_name, $new_last_name, $new_email, $new_pass_hash, $user_id);
							$stmt->execute();
							$stmt->close();
						} else {
							$messages[] = 'Error preparing statement to update user information';
						}
					
						if (empty($messages)) {
							// Refresh variables
							$first_name = $new_first_name;
							$last_name = $new_last_name;
							$email = $new_email;
							
							// Notify
							$success_messages[] = 'Users account information has been updated.';
						}
					}
				}
			}
		}
	} else {
		// User doesn't exist
		$error .= ' Please <a href="index.php">go back</a>.';
	}
?>
<html>
	<head>
		<title><?php title('User Info'); ?></title>
		<?php meta_tags(); ?>
		<link href='http://fonts.googleapis.com/css?family=Montserrat:400,700' rel='stylesheet' type='text/css' />
		<link rel="stylesheet" type="text/css" href="../style.css" media="screen">
		<script src="../js/jquery.min.js" type="text/javascript"></script>
		<script src="../js/jquery-scrollto.js" type="text/javascript"></script>
		<script src="../js/jquery.dataTables.min.js" type="text/javascript"></script>
		<script src="../js/jquery.zclip.min.js" type="text/javascript"></script>
		<script src="../js/main.js" type="text/javascript"></script>
		
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$('table#datatable').each(function() {
					$(this).dataTable({"bInfo": false, "bLengthChange": false, "sPaginationType": "full_numbers", "iDisplayLength": 50, "oLanguage": { "oPaginate": { "sFirst": "&laquo;&laquo;", "sPrevious": "&laquo;", "sNext": "&raquo;", "sLast": "&raquo;&raquo;" }}});
				});
				
				$('table#datatable tbody tr td a').truncate({width: '475px'});
				
				$('#urls button#delete').click(function() {
					if ($('#urls table tr td#on').length == 0) {
						alert('No rows are selected.');
						return;
					}
					
					var $form = $('<form action="<?php echo 'user.php?id='.$user_id; ?>" method="post" />');
					
					jQuery('#urls table tr').has('#on').each(function() {
						var $id = $(this).data('id');
						
						if ($id !== undefined)
							$form.append('<input type="hidden" name="ids[]" value="'+$id+'" />');
					});
					
					if ($form.length > 0) {
						$form.append('<input type="hidden" name="token" value="<?php echo $csrf_token ?>" />');
						$form.append('<input type="hidden" name="action" value="delete-urls" />');
						
						$('body').append($form);
						$form.submit();
					} else {
						alert('Unable to get ID from selected rows.');
						return;
					}
					
					
				});
			} );
		</script>
	</head>
	<body>
		<?php ganalytics_tracking(); ?>
		<?php output_errors(); ?>
		<div id="topmenu">
			<div id="nav">
				<div id="box">
					<ul>
						<li><a href="../">Home</a></li>
						<li><a href="../account.php">Account</a></li>
						<li><a href="../api-docs.php">API Documentation</a></li>
						<li><a href="../contact.php">Contact</a></li>
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
					<form action="../" method="post">
						<input type="hidden" name="token" value="<?php echo $csrf_token ?>" />
						<input type="text" name="url" id="url" placeholder="http://" value="" />
						<input type="submit" name="submit" id="submit" value="Shorten URL" />
					</form>
				</div>
			</div>
			<?php if (!$user_exists) : ?>
			<div id="bottom" style="height: 84px">
				<div id="notify" class="error"><?php echo $error; ?></div>
			</div>
			<?php else : ?>
			<div id="bottom">
				<div id="urls">
					<p>Users Shortened URLs</p>
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
							<?php foreach ($urls as $id => $url_info) : ?>
							<tr data-id="<?php echo $id; ?>">
								<td id="off">&nbsp;</td>
								<td><?php echo '<a href="'.htmlspecialchars($url_info['short_url_link']).'" target="_blank">'.htmlspecialchars($url_info['short_url_text']).'</a>'; ?></td>
								<td><?php echo '<a href="'.htmlspecialchars($url_info['long_url_link']).'" title="'.htmlspecialchars($url_info['long_url_link']).'" target="_blank">'.htmlspecialchars($url_info['long_url_text']).'</a>'; ?></td>
								<td><?php echo $url_info['visits']; ?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<button id="delete">Delete Selected</button>
				</div>
				<div id="account" style="height: 425px">
					<p>User Info</p>
					<form action="<?php echo 'user.php?id='.$user_id; ?>" method="post" id="user-info">
						<input type="hidden" name="token" value="<?php echo $csrf_token ?>" />
						<input type="hidden" name="action" value="update-user-info" />
						<span>(Items marked with * are optional)</span>
						<div class="clear"></div>
						<ul id="left">
							<li><label for="first">First Name</label><input type="text" name="first" id="first" value="<?php echo htmlspecialchars($first_name); ?>" /></li>
							<li><label for="last">Last Name</label><input type="text" name="last" id="last" value="<?php echo htmlspecialchars($last_name); ?>" /></li>
							<li><label for="email">E-mail</label><input type="text" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" /></li>
						</ul>
						<ul id="right">
							<li><label for="pass1">New Password <span class="optional">*</span></label><input type="text" name="pass1" id="pass1" value="" /></li>
							<li><label for="pass2">Confirm <span class="optional">*</span></label><input type="text" name="pass2" id="pass2" value="" /></li>

							<li><input type="submit" name="submit" value="Update" /></li>
						</ul>
					</form>
					<p><a href="index.php">Click here</a> to go back</p>
				</div>
			</div>
			<?php endif; ?>			
		</div>
		<div id="footer">
			<div id="copyright">Copyright &copy; 2013 <a href="http://www.little-apps.org" target="_blank">Little Apps</a>. All rights reserved.</div>
			<div id="navigation"><a href="../index.php">Home</a> | <a href="../account.php">Account</a> | <a href="../api-docs.php">API Documentation</a> | <a href="../contact.php">Contact</a></div>
		</div>
	</body>
	
</html>