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
	
	if ($logged_in == true) {
		// No need for forgot password, redirect to account
		redirect('account.php');
	}

	$show_reset_form = false;
	
	if ($csrf_valid == true) {
		if (isset($_POST['username'])) {
			$email = trim(strtolower($_POST['username']));
			
			if ($email == '') {
				$messages[] = "E-mail address cannot be empty.";
			} else {
				$stmt = $mysqli->prepare("SELECT id FROM `".MYSQL_PREFIX."users` WHERE email = ? AND activate_key = '' LIMIT 0,1");
				$stmt->bind_param('s', $email);
				$stmt->execute();
				
				$stmt->bind_result($user_id);
				
				if ($stmt->fetch() !== true) {
					$messages[] = "No e-mail address found.";
				}
				
				$stmt->close();
			}
			
			if (count($messages) == 0) {
				// Generate new activation key
				$activate_key = md5(uniqid('activate_'));
				
				$stmt = $mysqli->prepare("UPDATE `".MYSQL_PREFIX."users` SET reset_password_key=? WHERE id=?");
				$stmt->bind_param('si', $activate_key, $user_id);
				$stmt->execute();
				$stmt->close();
			
				// Send reset e-mail
				$subject = "Reset Your Password";
				
				$message = "This message is to alert you that someone requested a password reset." . PHP_EOL;
				$message .= "If you would like to reset your password, then please click the link below. Otherwise, please ignore this message." . PHP_EOL . PHP_EOL;
				$message .= "<" . SITE_URL . "/forgot-password.php?key=".$activate_key.">" . PHP_EOL . PHP_EOL;
				$message .= "This is an automated response, please do not reply!";
				
				send_email($email, '', SITE_NOREPLY, '', $subject, $message);
				
				$success_message = "An email was sent on how to reset your password.";
			}
	
		} else if (isset($_SESSION['user_id']) && isset($_SESSION['user_email']) && isset($_SESSION['user_first_name'])) {
			$new_password = ( (isset($_POST['password'])) ? trim($_POST['password']) : '' );
			$new_password_confirm = ( (isset($_POST['password-confirm'])) ? trim($_POST['password-confirm']) : '' );
			
			if ($new_password == '' && $new_password_confirm == '') {
				$messages[] = 'Passwords cannot be blank';
			} else {
				// Make sure passwords match
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
				
				if (count($messages) > 0) {
					$show_reset_form = true;
				} else {
					// Generate password hash
					$new_pass_hash = PassHash::hash($new_password);
					
					// Update password + clear reset key
					$stmt = $mysqli->prepare("UPDATE `".MYSQL_PREFIX."users` SET password=?, reset_password_key='' WHERE id=?");
					$stmt->bind_param('si', $new_pass_hash, $_SESSION['user_id']);
					$stmt->execute();
					$stmt->close();
					
					// Notify user
					$subject = "Your Password Has Been Updated";
					
					$message = "This e-mail is to notify you that your password has been updated." . PHP_EOL;
					$message .= "You can login to your account by visiting " . SITE_URL . "/login.php." . PHP_EOL;
					$message .= "If you didn't change your password, then you can reset it by visiting ".SITE_URL."/forgot-password.php." . PHP_EOL . PHP_EOL;
					$message .= "This is an automated response, please do not reply!";
					
					send_email($_SESSION['user_email'], $_SESSION['user_first_name'], SITE_NOREPLY, '', $subject, $message);
					
					// Reset session
					session_unset();
					session_destroy();
					
					// Redirect to login
					redirect('login.php');
				}
			}
		}
	} else if (isset($_GET['key'])) {
		$reset_key = $_GET['key'];
	
		$stmt = $mysqli->prepare("SELECT id, email, first_name FROM `".MYSQL_PREFIX."users` WHERE reset_password_key = ? LIMIT 0,1");
		$stmt->bind_param('s', $reset_key);
		$stmt->execute();
		
		$stmt->bind_result($user_id, $user_email, $first_name);
		
		if ($stmt->fetch() !== true) {
			$messages[] = 'Reset password key is invalid';
		} else {
			$_SESSION['user_id'] = $user_id;
			$_SESSION['user_email'] = $user_email;
			$_SESSION['user_first_name'] = $first_name;
			
			$show_reset_form = true;
		}
	}
?>
<html>
	<head>
		<title><?php title('Forgot Password'); ?></title>
		<?php meta_tags(); ?>
		
		<link href='http://fonts.googleapis.com/css?family=Montserrat:400,700' rel='stylesheet' type='text/css' />
		<link rel="stylesheet" type="text/css" href="style.css" media="screen">
		<script src="js/jquery.min.js" type="text/javascript"></script>
		<script src="js/jquery-scrollto.js" type="text/javascript"></script>
		<script src="js/jquery.zclip.min.js" type="text/javascript"></script>
		<script src="js/main.js" type="text/javascript"></script>
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
					<div id="register"><a href="#">Register</a></div>
					<div id="signin">
						<a href="#">Sign In</a>
						<div id="signinbox">
							<form action="login.php" method="post">
								<input type="hidden" name="action" value="login" />
								<input type="hidden" name="token" value="<?php echo $csrf_token; ?>" />
								<ul>
									<li><input type="text" name="username" id="username" placeholder="Email" value="" /></li>
									<li><input type="password" name="password" id="password" placeholder="Password" value="" /></li>
									<li>
										<span class="remember"><input type="checkbox" name="remember" id="remember" /><label for="remember"> Remember Me</label></span>
										<span class="forgot"><a href="forgot-password.php">Forgot Password?</a></span>
									</li>
									<li id="bottom">
										<div class="social">
											<?php if (FBLOGIN_ENABLED == true && defined('FBLOGIN_APPID') && defined('FBLOGIN_APPSECRET')) : ?><a href="<?php echo $fb_login_url; ?>"><img src="images/facebook.png" width="61" height="61" alt="Facebook login" /></a><?php endif; ?>
										</div>
										<div><input type="submit" name="submit" id="submit" /></div>
									</li>
								</ul>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div id="signinbox">
			<form action="login.php" method="post">
				<input type="hidden" name="token" value="<?php echo $csrf_token; ?>" />
				<input type="hidden" name="action" value="login" />
				<ul>
					<li><input type="text" name="username" id="username" placeholder="Email" value="" /></li>
					<li>
						<input type="password" name="password" id="password" placeholder="Password" value="" />
						<span><a href="forgot-password.php">Forgot Password?</a></span>
					</li>
					<li id="bottom">
						<span style="display: inline-block;"><input type="checkbox" name="remember" id="remember" /><label for="remember"> Remember Me</label></span>
						<span><input type="submit" name="submit" id="submit" /></span>
					</li>
				</ul>
			</form>
		</div>
		<div id="registerbox">
			<form action="login.php" method="post">
				<input type="hidden" name="token" value="<?php echo $csrf_token; ?>" />
				<input type="hidden" name="action" value="register" />
				<!-- left -->
				<ul>
					<li class="name"><label for="firstname">First Name</label><input type="text" name="firstname" id="firstname" value="" /></li>
					<li class="name"><label for="lastname">Last Name</label><input type="text" name="lastname" id="lastname" value="" /></li>
					<li class="birthdate">
						<label>Birth Date</label>
						<div id="day">
							<input type="text" name="birth[day]" id="day" value="1" readonly />
							<div class="combo-day" tabindex="0">
								<ul>
									<li>1</li>
									<li>2</li>
									<li>3</li>
									<li>4</li>
									<li>5</li>
									<li>6</li>
									<li>7</li>
									<li>8</li>
									<li>9</li>
									<li>10</li>
									<li>11</li>
									<li>12</li>
									<li>13</li>
									<li>14</li>
									<li>15</li>
									<li>16</li>
									<li>17</li>
									<li>18</li>
									<li>19</li>
									<li>20</li>
									<li>21</li>
									<li>22</li>
									<li>23</li>
									<li>24</li>
									<li>25</li>
									<li>26</li>
									<li>27</li>
									<li>28</li>
									<li>29</li>
									<li>30</li>
									<li>31</li>
								</ul>
							</div>
						</div>
						<div id="month">
							<input type="text" name="birth[month]" id="month" value="January" readonly />
							<div class="combo-month" tabindex="1">
								<ul>
									<li>January</li>
									<li>February</li>
									<li>March</li>
									<li>April</li>
									<li>May</li>
									<li>June</li>
									<li>July</li>
									<li>August</li>
									<li>September</li>
									<li>October</li>
									<li>November</li>
									<li>December</li>
								</ul>
							</div>
						</div>
						<div id="year">
							<input type="text" name="birth[year]" id="year" value="1965" readonly />
							<div class="combo-year" tabindex="2">
								<ul>
									<?php for ($i = $min_year; $i <= $max_year; $i++) echo "<li>".$i."</li>" . PHP_EOL; ?>
								</ul>
							</div>
						</div>
					</li>
				</ul>
				
				<!-- right -->
				<ul style="margin-left: 65px">
					<li class="email"><input type="text" name="email" id="email" value="" /><label for="email">E-mail</label></li>
					<li class="password"><input type="password" name="password" id="password" value="" /><label for="password">Password</label></li>
					<li class="submit">
						<div class="social">
							<?php if (FBLOGIN_ENABLED == true && defined('FBLOGIN_APPID') && defined('FBLOGIN_APPSECRET')) : ?><a href="<?php echo $fb_login_url; ?>"><img src="images/facebook.png" width="61" height="61" alt="Facebook login" /></a><?php endif; ?>
						</div>
						<input type="submit" name="submit" id="submit" />
					</li>
				</ul>
			</form>
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
			<div id="bottom" style="padding-bottom: 30px">
				<?php if (isset($success_message)) : ?><div id="notify"><?php echo $success_message; ?></div><?php endif; ?>
			<?php if ($show_reset_form == false) : ?>
				<p id="forgotpw-title">Please enter your e-mail to recover your password</p>
				<div id="forgotpw">
					<form action="forgot-password.php" method="post">
						<input type="hidden" name="token" value="<?php echo $csrf_token; ?>" />
						<ul>
							<li><input type="text" name="username" id="username" placeholder="Email" value="" /></li>
							<li id="bottom">
								<span><input type="submit" name="submit" id="submit" /></span>
							</li>
						</ul>
					</form>
				</div>
			<?php else : ?>
				<p id="resetpw-title">Please enter your new password below</p>
				<div id="resetpw">
					<form action="forgot-password.php" method="post">
						<input type="hidden" name="token" value="<?php echo $csrf_token; ?>" />
						<ul>
							<li><input type="password" name="password" id="password" placeholder="Password" value="" /></li>
							<li><input type="password" name="password-confirm" id="password-confirm" placeholder="Confirm Password" value="" /></li>
							<li id="bottom">
								<span><input type="submit" name="submit" id="submit" /></span>
							</li>
						</ul>
					</form>
				</div>
			<?php endif; ?>
			</div>			
		</div>
		<div id="footer">
			<div id="copyright"><a href="https://github.com/little-apps/little-url-shortener" target="_blank">Little URL Shortener</a> was developed by <a href="http://www.little-apps.com" target="_blank">Little Apps</a> and is licensed under the <a href="http://www.gnu.org/licenses/gpl.html" target="_blank">GNU GPLv3</a>.</div>
			<div id="navigation"><a href="index.php">Home</a> | <a href="account.php">Account</a> | <a href="api-docs.php">API Documentation</a> | <a href="contact.php">Contact</a></div>
		</div>
	</body>
	
</html>