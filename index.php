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
	
	if ((isset($_GET['code'])) && $_GET['code'] != '') {
		$code = ltrim($_GET['code'], '/');
		
		if (strlen($code) != SITE_SHORTURLLENGTH) {
			$messages[] = 'URL code is not the right length';
		} else {
			// Lookup code
			$stmt = $mysqli->prepare("SELECT id, long_url FROM `".MYSQL_PREFIX."urls` WHERE short_url = ? LIMIT 0,1");
			$stmt->bind_param('s', $code);
			$stmt->execute();
			
			$stmt->bind_result($url_id, $url_long);
			
			if ($stmt->fetch() !== true) {
				$messages[] = 'URL code could not be found';
				
				$stmt->close();
			} else {
				$stmt->close();
				
				// Increase visits
				$stmt = $mysqli->prepare("UPDATE `".MYSQL_PREFIX."urls` SET visits=visits+1 WHERE id=?");
				$stmt->bind_param('i', $url_id);
				$stmt->execute();
				$stmt->close();
				
				// Redirect
				redirect($url_long);
			}
		}
	}
?>
<html>
	<head>
		<title><?php title('Home'); ?></title>
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
				<div class="info">
					<div class="title">What is 7LS.NET?</div>
					<div class="answer">7LS.NET is an website designed to allow users to shorten URLs quickly and easily. It is also part of an open source project.</div>
				</div>
				<div class="stats">
					<div class="title">Real Time Statistics</div>
					<div class="boxes">
						<div class="box">
							<div class="top">Total URLs</div>
							<span class="number" id="urls-count">N/A</span>
						</div>
						<div class="box">
							<div class="top">Total Visits</div>
							<span class="number" id="visits-count">N/A</span>
						</div>
						<div class="box">
							<div class="top">Total Users</div>
							<span class="number" id="users-count">N/A</span>
						</div>
					</div>
				</div>
				<div class="clear"></div>
				<div class="features">
					<p>Features</p>
					<div id="feature">
						<ul>
							<li><img src="images/features/api.png" alt="API" width="64" height="64" /></li>
							<li>The API allows developers to create shortened links simply and easily.</li>
						</ul>
					</div>
					<div id="feature">
						<ul>
							<li><img src="images/features/share.png" alt="Sharing" width="64" height="64" /></li>
							<li>Users can share URLs with others through one of the many social networks with just one click.</li>
						</ul>
					</div>
					<div id="feature">
						<ul>
							<li><img src="images/features/stats.png" alt="Stats" width="64" height="64" /></li>
							<li>You can monitor your shortened URL to find out how many people have visited it</li>
						</ul>
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