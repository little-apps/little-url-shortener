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
	
	$name = '';
	$email = '';
	$message = '';
	
	if ($logged_in == true) {
		// Lookup user info
		$stmt = $mysqli->prepare("SELECT CONCAT_WS(' ', first_name, last_name), email FROM `".MYSQL_PREFIX."users` WHERE id = ? LIMIT 0,1");
		$stmt->bind_param('i', $_SESSION['user_id']);
		$stmt->execute();
		
		$stmt->bind_result($name, $email);

		if ($stmt->fetch() !== true) {
			$name = '';
			$email = '';
		}
	
		$stmt->close();
	}
	
	if ($csrf_valid == true) {
		$name = ( (isset($_POST['name'])) ? trim($_POST['name']) : '' );
		$email = ( (isset($_POST['email'])) ? trim($_POST['email']) : '' );
		$message = ( (isset($_POST['message'])) ? trim($_POST['message']) : '' );
		
		if ($name == '') {
			$messages[] = "Name cannot be blank";
		}
		
		if ($email == '') {
			$messages[] = "E-mail cannot be empty";
		} else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$messages[] = 'E-mail is invalid';
		}
		
		if ($message == '') {
			$messages[] = "Message cannot be blank";
		}
		
		if (count($messages) == 0) {
			// Send e-mail
			$subject = "Message Recieved";
			
			$text = "This is to notify you that the following message was sent by ".$name." (".$email."):" . PHP_EOL . PHP_EOL;
			$text .= $message . PHP_EOL . PHP_EOL;
			$text .= "If you would like to reply, simply reply to this e-mail." . PHP_EOL . PHP_EOL;
			$text .= "IP address: " . $_SERVER['REMOTE_ADDR'] . PHP_EOL;
			
			send_email(SITE_ADMINEMAIL, '', $email, $name, $subject, $text);
			
			// Clear variables
			if ($logged_in == false) {
				$name = '';
				$email = '';
			}
			
			$message = '';
			
			// Notify
			$success_message = "We'll be in contact with you soon.";
		
		}
	}
?>
<html>
	<head>
		<title><?php title('Contact'); ?></title>
		<?php meta_tags(); ?>
		
		<link href='http://fonts.googleapis.com/css?family=Montserrat:400,700' rel='stylesheet' type='text/css' />
		<link rel="stylesheet" type="text/css" href="style.css" media="screen">
		<script src="js/jquery.min.js" type="text/javascript"></script>
		<script src="js/jquery-ui-1.10.2.custom.min.js" type="text/javascript"></script>
		<script src="js/jquery-scrollto.js" type="text/javascript"></script>
		
		<link rel="stylesheet" type="text/css" href="js/highlight/default.css">
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
<?php if ($logged_in == true) : ?>
				<div id="login">
					<div id="logout"><a href="login.php?action=logout">Sign Out</a></div>
				</div>
			</div>
		</div>
<?php else : ?>
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
		<div id="registerbox">
			<form action="login.php" method="post">
				<input type="hidden" name="action" value="register" />
				<input type="hidden" name="token" value="<?php echo $csrf_token; ?>" />
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
<?php endif; ?>
		<div id="wrapper">
			<div id="contact">
				<?php if (isset($success_message)) : ?><div id="notify"><?php echo $success_message ?></div><?php endif; ?>
				<p id="title">If you would like to contact us, please use the form below</p>
				<form action="contact.php" method="post">
					<input type="hidden" name="token" value="<?php echo $csrf_token; ?>" />
					<ul>
						<li><label for="name">Name</label><input type="text" name="name" id="name" value="<?php echo $name ?>" /></li>
						<li><label for="email">Email</label><input type="text" name="email" id="email" value="<?php echo $email ?>" /></li>
						<li style="height: 312px"><label for="message">Message</label><textarea name="message" id="message"><?php echo $message ?></textarea></li>
						<li><input type="submit" name="submit" id="submit" value="Send" /></li>
					</ul>
				</form>
			</div>		
		</div>
		<div id="footer">
			<div id="copyright"><a href="https://github.com/little-apps/little-url-shortener" target="_blank">Little URL Shortener</a> was developed by <a href="http://www.little-apps.com" target="_blank">Little Apps</a> and is licensed under the <a href="http://www.gnu.org/licenses/gpl.html" target="_blank">GNU GPLv3</a>.</div>
			<div id="navigation"><a href="index.php">Home</a> | <a href="account.php">Account</a> | <a href="api-docs.php">API Documentation</a> | <a href="contact.php">Contact</a></div>
		</div>
	</body>
	
</html>