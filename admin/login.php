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
	
	if ($admin_logged_in && $_GET['action'] != 'logout') {
		redirect('index.php');
	}
	
	if ((isset($_GET['action'])) && $_GET['action'] == 'logout') {
		// Clear session
		session_unset();
		session_destroy();
		
		// Create new session
		session_start();
		session_regenerate_id(true);
		
		// Regenerate CSRF token
		$csrf_token = md5(uniqid());
		$_SESSION['csrf_token'] = $csrf_token;
		
		$success_message = "You've been signed out.";
	}
	
	if (isset($_SERVER['REQUEST_METHOD']) && strcasecmp($_SERVER['REQUEST_METHOD'], 'post') == 0) {
		if (!$csrf_valid) {
			$messages[] = 'Cross-site Request Forgery (CSRF) token is invalid.';
		} else if (!isset($_POST['username']) || !isset($_POST['password'])) {
			$messages[] = 'Missing username and/or password.';
		} else if (trim($_POST['username']) == '' || trim($_POST['password']) == '') {
			$messages[] = 'Username and password cannot be empty.';
		}
		
		if (empty($messages)) {
			$username = trim(strtolower($_POST['username']));
			$password = trim($_POST['password']);
		
			// Lookup user
			$stmt = $mysqli->prepare("SELECT id, password FROM `".MYSQL_PREFIX."admins` WHERE username = ? LIMIT 0,1");
			$stmt->bind_param('s', $username);
			$stmt->execute();
			
			$stmt->bind_result($admin_id, $pass_hash);
			
			if ($stmt->fetch() !== true) {
				$messages[] = 'Username/password is invalid';

				$stmt->close();
			} else {
				$stmt->close();

				if (PassHash::check_password($pass_hash, $password)) {
					// Regenerate session ID to prevent session hijacking
					session_regenerate_id();
					
					// Get users IP address
					$user_ip = ((SITE_VALIDATEIP == true) ? $_SERVER['REMOTE_ADDR'] : '');
					
					$_SESSION['admin_id'] = $admin_id;
					$_SESSION['admin_hash'] = md5($admin_id.$pass_hash.$user_ip);

					redirect('index.php');
				} else {
					$messages[] = 'Username/password is invalid';
				}
			}
			
			// Clear results
			unset($username, $password, $pass_hash, $user_ip);
		}
		
	}
?>
<html>
	<head>
		<title><?php title('Admin Login'); ?></title>
		<?php meta_tags(); ?>
		<link href='http://fonts.googleapis.com/css?family=Montserrat:400,700' rel='stylesheet' type='text/css' />
		<link rel="stylesheet" type="text/css" href="../style.css" media="screen">
		<script src="../js/jquery.min.js" type="text/javascript"></script>
		<script src="../js/jquery.zclip.min.js" type="text/javascript"></script>
		<script src="../js/main.js" type="text/javascript"></script>
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
					<div id="register"><a href="#">Register</a></div>
					<div id="signin"><a href="#">Sign In</a></div>
				</div>
			</div>
		</div>
		<div id="signinbox">
			<form action="../login.php" method="post">
				<input type="hidden" name="action" value="login" />
				<input type="hidden" name="token" value="<?php echo $csrf_token; ?>" />
				<ul>
					<li><input type="text" name="username" id="username" placeholder="Email" value="" /></li>
					<li><input type="password" name="password" id="password" placeholder="Password" value="" /></li>
					<li id="bottom">
						<span style="display: inline-block;"><input type="checkbox" name="remember" id="remember" /><label for="remember"> Remember Me</label></span>
						<span><input type="submit" name="submit" id="submit" /></span>
					</li>
				</ul>
			</form>
		</div>
		<div id="registerbox">
			<form action="../login.php" method="post">
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
					<li class="submit"><input type="submit" name="submit" id="submit" /></li>
				</ul>
			</form>
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
			<div id="bottom" style="padding-bottom: 30px">
				<p id="signin-title">Please login to access your account</p>
				<div id="signin" style="height: 232px">
					<form action="login.php" method="post">
						<input type="hidden" name="token" value="<?php echo $csrf_token; ?>" />
						<ul>
							<li><input type="text" name="username" id="username" placeholder="Username" value="" /></li>
							<li><input type="password" name="password" id="password" placeholder="Password" value="" /></li>
							<li id="bottom">
								<span><input type="submit" name="submit" id="submit" /></span>
							</li>
						</ul>
					</form>
				</div>
			</div>
		</div>
		<div id="footer">
			<div id="copyright">Copyright &copy; 2013 <a href="http://www.little-apps.org" target="_blank">Little Apps</a>. All rights reserved.</div>
			<div id="navigation"><a href="../index.php">Home</a> | <a href="../account.php">Account</a> | <a href="../api-docs.php">API Documentation</a> | <a href="../contact.php">Contact</a></div>
		</div>
	</body>
	
</html>