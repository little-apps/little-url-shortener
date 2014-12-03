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
	
	if ($logged_in == true && $_GET['action'] != 'logout') {
		redirect('account.php');
	}
	
	$first_name_register_default = '';
	$last_name_register_default = '';
	$birth_day_register_default = '1';
	$birth_month_register_default = 'January';
	$birth_year_register_default = '1965';
	$email_register_default = '';
	$email_signin_default = '';
	
	if ((isset($_GET['action'])) && $_GET['action'] == 'logout') {
		// Clear session
		session_unset();
		session_destroy();
		
		// Clear cookies (if they exist)
		if (isset($_COOKIE['7LSNETUID']) || isset($_COOKIE['7LSNETHASH'])) {
			setcookie("7LSNETUID", "", time()-3600);
			setcookie("7LSNETHASH", "", time()-3600);
		}
		
		if ($fb_logged_in === true) {
			if (!isset($facebook)) {
				require_once(dirname(__FILE__).'/inc/facebook-api/facebook.php');
				$facebook = new Facebook(array('appId'  => FBLOGIN_APPID, 'secret' => FBLOGIN_APPSECRET));
			}
		
			$facebook->destroySession();
			
			// Get login URL
			$fb_login_url = $facebook->getLoginUrl(array('scope' => 'email,user_about_me,user_birthday', 'redirect_uri' => SITE_URL . '/account.php'));
		}
		
		// Create new session
		session_start();
		session_regenerate_id(true);
		
		// Regenerate CSRF token
		$csrf_token = md5(uniqid());
		$_SESSION['csrf_token'] = $csrf_token;
		
		$success_message = "You've been signed out.";
	} else if (isset($_GET['activate'])) {
		$activate_key = $_GET['activate'];
		
		$stmt = $mysqli->prepare("SELECT id, email, first_name FROM `".MYSQL_PREFIX."users` WHERE activate_key = ? LIMIT 0,1");
		$stmt->bind_param('s', $activate_key);
		$stmt->execute();
		
		$stmt->bind_result($user_id, $user_email, $first_name);
		
		if ($stmt->fetch() !== true) {
			$messages[] = "Activation key is invalid.";
			
			$stmt->close();
		} else {
			$stmt->close();
			
			$stmt = $mysqli->prepare("UPDATE `".MYSQL_PREFIX."users` SET activate_key = '' WHERE id=?");
			$stmt->bind_param('i', $user_id);
			$stmt->execute();
			$stmt->close();			
						
			$subject = "Welcome to ".SITE_NAME;
				
			$message = "Thank you for registering at ".SITE_NAME.". Below is your login information:" . PHP_EOL . PHP_EOL;
			$message .= "Username: " . $user_email . PHP_EOL . PHP_EOL;
			$message .= "You can login to your account by visiting ".SITE_URL."/login.php." . PHP_EOL;
			$message .= "Please keep this e-mail for your records." . PHP_EOL;
			$message .= "This is an automated response, please do not reply!";
			
			send_email($user_email, $first_name, SITE_NOREPLY, '', $subject, $message);
			
			$success_message = "Your account has been activated. You can login below.";
		}
	} else if ((isset($_POST['action'])) && $csrf_valid == true) {
		if ($_POST['action'] == 'register') {
			$months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
		
			$first_name = trim(preg_replace('/[\s]+/is', ' ', $_POST['firstname']));
			$last_name = trim(preg_replace('/[\s]+/is', ' ', $_POST['lastname']));
			$birth_day = $_POST['birth']['day'];
			$birth_month = $_POST['birth']['month'];
			$birth_year = $_POST['birth']['year'];
			$email = trim(strtolower($_POST['email']));
			$password = trim($_POST['password']);

			if (!preg_match('/^[a-z\s]+$/i', $first_name)) {
				$messages[] = 'First name is invalid';
			}
			
			if (!preg_match('/^[a-z\s]+$/i', $last_name)) {
				$messages[] = 'Last name is invalid';
			}
			
			if (!is_numeric($birth_day) || ($birth_day < 1 || $birth_day > 31)) {
				$birth_day = 1;
				$messages[] = 'Birth day is invalid';
			}
			
			if (!in_array($birth_month, $months)) {
				$birth_month = $months[0];
				$messages[] = 'Birth month is invalid';
			}
			
			if ($birth_year < $min_year || $birth_year > $max_year) {
				$birth_year = '1965';
				$messages[] = 'Birth year is invalid';
			}
			
			// Check if email already registered
			if ($result = $mysqli->query("SELECT * FROM `".MYSQL_PREFIX."users` WHERE `email` = '".$mysqli->escape_string($email)."'")) {
				if ($result->num_rows > 0)
					$messages[] = 'Email address is already registered';
			}
			
			// Valid e-mail
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$messages[] = 'Email is invalid';
			}
			
			// Test password strength
			if (!preg_match('@[A-Z]@', $password) || !preg_match('@[a-z]@', $password) || !preg_match('@[0-9]@', $password)) {
				$messages[] = 'Password must contain at least one uppercase (A-Z), lowercase (a-z) and number (0-9)';
			}
			
			// Make sure there's no white spaces (ie: tabs, spaces)
			if (preg_match('/\s+/', $password)) {
				$messages[] = 'Password cannot contain white spaces (spaces, tabs)';
			}
			
			if (strlen($password) < 8) {
				$messages[] = 'Password must be at least eight characters long';
			}
			
			if (count($messages) == 0) {
				// Convert birthdate to valid string format
				$birthdate = sprintf("%04d-%02d-%02d", $birth_year, date('m', strtotime($birth_month)), $birth_day);
			
				// Hash password
				$pass_hash = PassHash::hash($password);
				
				// Generate API Key
				$api_key = md5(uniqid('api_'));
				
				// Generate activation key
				$activate_key = md5(uniqid('activate_'));
			
				// Insert new user
				$stmt = $mysqli->prepare("INSERT INTO `".MYSQL_PREFIX."users` (`first_name`,`last_name`,`email`,`birthday`,`password`,`api_key`,`activate_key`) VALUES (?,?,?,?,?,?,?)");
				$stmt->bind_param('sssssss', $first_name, $last_name, $email, $birthdate, $pass_hash, $api_key, $activate_key);
				$stmt->execute();
				$stmt->close();
				
				// Send welcome e-mail
				$subject = "Activate your account";
				
				$message = "Somebody created an account at ".SITE_NAME." with this e-mail." . PHP_EOL;
				$message .= "If you created this account, please click the link below to activate your account. Otherwise, please ignore this e-mail." .PHP_EOL.PHP_EOL;
				$message .= "<".SITE_URL."/login.php?activate=".$activate_key.">".PHP_EOL.PHP_EOL;
				$message .= "This is an automated response, please do not reply!";
				
				send_email($email, $first_name, SITE_NOREPLY, '', $subject, $message);
				
				$success_message = "An e-mail has been sent to activate your account.";
			} else {
				$first_name_register_default = $first_name;
				$last_name_register_default = $last_name;
				$birth_day_register_default = $birth_day;
				$birth_month_register_default = $birth_month;
				$birth_year_register_default = $birth_year;
				$email_register_default = $email;
			}
		} else if ($_POST['action'] == 'login') {
			$email = ( ( isset($_POST['username']) ) ? ( trim(strtolower($_POST['username'])) ) : ( '' ) );
			$password = ( ( isset($_POST['password']) ) ? ( trim($_POST['password']) ) : ( '' ) );
			
			if ($email == '') {
				$messages[] = 'No e-mail specified';
			}
			
			if ($password == '') {
				$email_signin_default = $email;
				$messages[] = 'No password entered';
			}
			
			if (count($messages) == 0) {
				$stmt = $mysqli->prepare("SELECT id, password, reset_password_key FROM `".MYSQL_PREFIX."users` WHERE email = ? AND activate_key = '' LIMIT 0,1");
				$stmt->bind_param('s', $email);
				$stmt->execute();
				
				$stmt->bind_result($user_id, $pass_hash, $reset_password_key);
				
				if ($stmt->fetch() !== true) {
					$messages[] = 'Username was not found';

					$stmt->close();
				} else {
					$stmt->close();

					if (PassHash::check_password($pass_hash, $password)) {
						// If password reset key is set, unset it
						if ($reset_password_key != '') {
							$stmt = $mysqli->prepare("UPDATE `".MYSQL_PREFIX."users` SET reset_password_key = '' WHERE id=?");
							$stmt->bind_param('i', $user_id);
							$stmt->execute();
							$stmt->close();
						}

						// Regenerate session ID to prevent session hijacking
						session_regenerate_id();
						
						// Get users IP address
						$user_ip = ((SITE_VALIDATEIP == true) ? $_SERVER['REMOTE_ADDR'] : '');
						
						$_SESSION['user_id'] = $user_id;
						$_SESSION['user_hash'] = md5($user_id.$email.$pass_hash.$user_ip);
						
						if (isset($_POST['remember'])) {
							setcookie( "7LSNETUID", $_SESSION['user_id'], time() + 60 * 60 * 24 * 365 );
							setcookie( "7LSNETHASH", $_SESSION['user_hash'], time() + 60 * 60 * 24 * 365 );
						}
						
						redirect('account.php');
					} else {
						$messages[] = 'Password is invalid';
					}
				}
			}
			
		}
	
	}
	
?>
<html>
	<head>
		<title><?php title('Login'); ?></title>
		<?php meta_tags(); ?>
		
		<link href='http://fonts.googleapis.com/css?family=Montserrat:400,700' rel='stylesheet' type='text/css' />
		<link rel="stylesheet" type="text/css" href="style.css" media="screen">
		<script src="js/jquery.min.js" type="text/javascript"></script>
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
		<div id="registerbox">
			<form action="login.php" method="post">
				<input type="hidden" name="token" value="<?php echo $csrf_token; ?>" />
				<input type="hidden" name="action" value="register" />
				<!-- left -->
				<ul>
					<li class="name"><label for="firstname">First Name</label><input type="text" name="firstname" id="firstname" value="<?php echo htmlspecialchars($first_name_register_default); ?>" /></li>
					<li class="name"><label for="lastname">Last Name</label><input type="text" name="lastname" id="lastname" value="<?php echo htmlspecialchars($last_name_register_default); ?>" /></li>
					<li class="birthdate">
						<label>Birth Date</label>
						<div id="day">
							<input type="text" name="birth[day]" id="day" value="<?php echo htmlspecialchars($birth_day_register_default); ?>" readonly />
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
							<input type="text" name="birth[month]" id="month" value="<?php echo htmlspecialchars($birth_month_register_default); ?>" readonly />
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
							<input type="text" name="birth[year]" id="year" value="<?php echo htmlspecialchars($birth_year_register_default); ?>" readonly />
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
					<li class="email"><input type="text" name="email" id="email" value="<?php echo htmlspecialchars($email_register_default); ?>" /><label for="email">E-mail</label></li>
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
				<p id="signin-title">Please login to access your account</p>
				<div id="signin">
					<form action="login.php" method="post">
						<input type="hidden" name="token" value="<?php echo $csrf_token; ?>" />
						<input type="hidden" name="action" value="login" />
						<ul>
							<li><input type="text" name="username" id="username" placeholder="Email" value="<?php echo htmlspecialchars($email_signin_default); ?>" /></li>
							<li><input type="password" name="password" id="password" placeholder="Password" value="" /></li>
							<li>
								<span class="remember"><input type="checkbox" name="remember" id="remember-page" /><label for="remember-page"> Remember Me</label></span>
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
		<div id="footer">
			<div id="copyright"><a href="https://github.com/little-apps/little-url-shortener" target="_blank">Little URL Shortener</a> was developed by <a href="http://www.little-apps.com" target="_blank">Little Apps</a> and is licensed under the <a href="http://www.gnu.org/licenses/gpl.html" target="_blank">GNU GPLv3</a>.</div>
			<div id="navigation"><a href="index.php">Home</a> | <a href="account.php">Account</a> | <a href="api-docs.php">API Documentation</a> | <a href="contact.php">Contact</a></div>
		</div>
	</body>
	
</html>