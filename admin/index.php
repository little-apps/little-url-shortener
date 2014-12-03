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
	
	if ($admin_logged_in == false) {
		redirect('login.php');
	}
	
	function refresh_arrays() {
		global $mysqli, $messages, $urls, $users;
	
		$urls = array();
		$users = array();
		
		if ($stmt = $mysqli->prepare("SELECT id, short_url, long_url, visits, user FROM `".MYSQL_PREFIX."urls` ORDER BY id")) {
			$stmt->execute();
			
			$stmt->bind_result($url_id, $short_url, $long_url, $visits, $user_id);
			
			while ($stmt->fetch()) {
				$short_url_link = ( substr_compare($long_url, 'https', 0, 5, true) == 0 ? SITE_SSLURL : SITE_URL ) .'/'. $short_url;
				$short_url_text = str_replace('http://', '', SITE_URL) . '/' . $short_url;
				
				$long_url_link = $long_url;
				$long_url_text = str_replace(array('http://', 'https://'), '', $long_url);
			
				$urls[$url_id] = array('short_url_link' => $short_url_link, 'short_url_text' => $short_url_text, 'long_url_link' => $long_url_link, 'long_url_text' => $long_url_text, 'visits' => $visits, 'user_id' => $user_id);
			}
			
			$stmt->close();
		} else {
			$messages[] = 'Error preparing SQL statement';
		}
		
		if ($stmt = $mysqli->prepare("SELECT users.id, users.first_name, users.last_name, COUNT(urls.id), COALESCE(SUM(urls.visits),0) FROM `".MYSQL_PREFIX."users` AS users LEFT JOIN `".MYSQL_PREFIX."urls` AS urls ON users.id = urls.user GROUP BY users.id ORDER BY users.id")) {
			$stmt->execute();
			
			$stmt->bind_result($user_id, $first_name, $last_name, $user_urls, $user_visits);
			
			while ($stmt->fetch()) {
				$users[$user_id] = array('first_name' => $first_name, 'last_name' => $last_name, 'urls' => $user_urls, 'visits' => $user_visits);
			}
			
			$stmt->close();
		}
	}
	
	refresh_arrays();
	
	if (isset($_SERVER['REQUEST_METHOD']) && strcasecmp($_SERVER['REQUEST_METHOD'], 'post') == 0) {
		if (!$csrf_valid) {
			$messages[] = 'Cross-site Request Forgery (CSRF) token is invalid.';
		} else if (!isset($_POST['action'])) {
			$messages[] = 'No action specified.';
		} else if (!in_array($_POST['action'], array('delete-urls', 'delete-users'))) {
			$messages[] = 'Invalid action specified.';
		}
		
		if (empty($messages)) {
			if ($_POST['action'] == 'delete-urls') {
				if ((!isset($_POST['ids'])) || !is_array($_POST['ids'])) {
					$messages[] = 'No IDs were specified.';
				} else {
					// Build query
					$sql = "DELETE FROM `".MYSQL_PREFIX."urls` WHERE ";
					
					foreach ($_POST['ids'] as $id) {
						if (is_numeric($id))
							$sql .= "id = ".intval($id)." OR ";
					}
					
					$sql = substr($sql, 0, -4);
					
					// Remove rows
					if ($mysqli->query($sql)) {
						// Refresh arrays
						refresh_arrays();
						
						// Success
						$success_messages[] = 'Selected URLs have been deleted.';
					} else {
						$messages[] = 'There was an error removing the URLs.';
					}
				}
			} else if ($_POST['action'] == 'delete-users') {
				if ((!isset($_POST['ids'])) || !is_array($_POST['ids'])) {
					$messages[] = 'No IDs were specified.';
				} else if ((!isset($_POST['remove-urls'])) && !in_array($_POST['remove-urls'], array('true', 'false'))) {
					$messages[] = 'Missing true/false value to remove URLs.';
				} else {
					if ($_POST['remove-urls'] == 'true') {
						// Build query
						$sql = "DELETE users, urls FROM `".MYSQL_PREFIX."users` AS users JOIN `".MYSQL_PREFIX."urls` AS urls ON users.id = urls.user WHERE ";
						
						foreach ($_POST['ids'] as $id) {
							if (is_numeric($id))
								$sql .= "users.id = ".intval($id)." OR ";
						}
						
						$sql = substr($sql, 0, -4);
						
						// Remove rows
						if ($mysqli->query($sql)) {
							// Refresh arrays
							refresh_arrays();
							
							// Success
							$success_messages[] = 'Selected users and their URLs have been deleted.';
						} else {
							$messages[] = 'There was an error removing the URLs.';
						}
					} else {
						// Build query
						$sql = "DELETE FROM `".MYSQL_PREFIX."users` WHERE ";
						
						foreach ($_POST['ids'] as $id) {
							if (is_numeric($id))
								$sql .= "id = ".intval($id)." OR ";
						}
						
						$sql = substr($sql, 0, -4);
						
						// Remove rows
						if ($mysqli->query($sql)) {
							// Refresh arrays
							refresh_arrays();
							
							// Success
							$success_messages[] = 'Selected users have been deleted.';
						} else {
							$messages[] = 'There was an error removing the URLs.';
						}
					}
				}
			}
		}
	}
?>
<html>
	<head>
		<title><?php title('Admin Area'); ?></title>
		<?php meta_tags(); ?>
		<link href='http://fonts.googleapis.com/css?family=Montserrat:400,700' rel='stylesheet' type='text/css' />
		<link rel="stylesheet" type="text/css" href="../style.css" media="screen">
		<script src="../js/jquery.min.js" type="text/javascript"></script>
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
					
					if (!confirm('Are you sure you want to delete these URLs?'))
						return;
					
					var $form = $('<form action="index.php" method="post" />');
					
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
				
				$('#users button#delete').click(function() {
					if ($('#users table tr td#on').length == 0) {
						alert('No rows are selected.');
						return;
					}
					
					if (!confirm('Are you sure you want to delete these users?'))
						return;
						
					var $remove_urls = 'false';
					
					if (confirm('Would you like to remove the URLs associated with these users?'))
						$remove_urls = 'true';
					
					var $form = $('<form action="index.php" method="post" />');
					
					jQuery('#users table tr').has('#on').each(function() {
						var $id = $(this).data('id');
						
						if ($id !== undefined)
							$form.append('<input type="hidden" name="ids[]" value="'+$id+'" />');
					});
					
					if ($form.length > 0) {
						$form.append('<input type="hidden" name="token" value="<?php echo $csrf_token ?>" />');
						$form.append('<input type="hidden" name="action" value="delete-users" />');
						$form.append('<input type="hidden" name="remove-urls" value="'+$remove_urls+'" />');
						
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
								<th>User ID</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($urls as $id => $url_info) : ?>
							<tr data-id="<?php echo $id; ?>">
								<td id="off">&nbsp;</td>
								<td class="padding"><?php echo '<a href="'.htmlspecialchars($url_info['short_url_link']).'" target="_blank" title="'.htmlspecialchars($url_info['long_url_text']).'">'.htmlspecialchars($url_info['short_url_text']).'</a>'; ?></td>
								<td class="padding"><?php echo '<a href="'.htmlspecialchars($url_info['long_url_link']).'" target="_blank" title="'.htmlspecialchars($url_info['long_url_text']).'">'.htmlspecialchars($url_info['long_url_text']).'</a>'; ?></td>
								<td class="padding"><?php echo $url_info['visits']; ?></td>
								<?php if ($url_info['user_id'] == 0) : ?>
								<td>None</td>
								<?php else : ?>
								<td><?php echo '<a href="user.php?id='.$url_info['user_id'].'">'.$url_info['user_id'].'</a>'; ?></td>
								<?php endif; ?>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<button id="delete">Delete Selected</button>
				</div>
				<div id="users" style="padding-bottom: 155px">
					<p>Users</p>
					<table id="datatable">
						<thead>
							<tr>
								<th></th>
								<th>First Name</th>
								<th>Last Name</th>
								<th>URLs</th>
								<th>Visits</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($users as $id => $user_info) : ?>
							<tr data-id="<?php echo $id; ?>">
								<td id="off">&nbsp;</td>
								<td class="padding"><?php echo htmlspecialchars($user_info['first_name']); ?></td>
								<td class="padding"><?php echo htmlspecialchars($user_info['last_name']); ?></td>
								<td class="padding"><?php echo $user_info['urls']; ?></td>
								<td class="padding"><?php echo $user_info['visits']; ?></td>
								<td><a href="<?php echo 'user.php?id='.$id; ?>" class="edit"><img src="../images/edit.png" height="16" width="16" alt="Edit" /></a></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<button id="delete">Delete Selected</button>
				</div>
			</div>			
		</div>
		<div id="footer">
			<div id="copyright">Copyright &copy; 2013 <a href="http://www.little-apps.org" target="_blank">Little Apps</a>. All rights reserved.</div>
			<div id="navigation"><a href="../index.php">Home</a> | <a href="../account.php">Account</a> | <a href="../api-docs.php">API Documentation</a> | <a href="../contact.php">Contact</a></div>
		</div>
	</body>
	
</html>