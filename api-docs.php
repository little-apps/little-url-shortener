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
	
	$api_key = '';
	
	// If logged in -> get API key
	if ($logged_in == true) {
		// Lookup user info
		$stmt = $mysqli->prepare("SELECT api_key FROM `".MYSQL_PREFIX."users` WHERE id = ? LIMIT 0,1");
		$stmt->bind_param('i', $_SESSION['user_id']);
		$stmt->execute();
		
		$stmt->bind_result($api_key);

		if ($stmt->fetch() !== true) {
			$api_key = '';
		}
	
		$stmt->close();
	}
?>
<html>
	<head>
		<title><?php title('API Documentation'); ?></title>
		<?php meta_tags(); ?>
		
		<link href='http://fonts.googleapis.com/css?family=Montserrat:400,700' rel='stylesheet' type='text/css' />
		<link rel="stylesheet" type="text/css" href="style.css" media="screen">
		<script src="js/jquery.min.js" type="text/javascript"></script>
		<script src="js/jquery-ui-1.10.2.custom.min.js" type="text/javascript"></script>
		<link rel="stylesheet" type="text/css" href="js/highlight/default.css">
		<script src="js/highlight/highlight.pack.js" type="text/javascript"></script>
		<script src="js/jquery.zclip.min.js" type="text/javascript"></script>

		<script src="js/main.js" type="text/javascript"></script>
		<script type="text/javascript">
			jQuery(document).ready(function($) { 
				$("#tabs").tabs();
				$('pre code').each(function(i, e) {hljs.highlightBlock(e)});
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
		<div id="signinbox">
			<form action="login.php" method="post">
				<input type="hidden" name="action" value="login" />
				<input type="hidden" name="token" value="<?php echo $csrf_token; ?>" />
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
						<input type="hidden" name="token" value="<?php echo $csrf_token; ?>" />
						<input type="text" name="url" id="url" placeholder="http://" value="" />
						<input type="submit" name="submit" id="submit" value="Shorten URL" />
					</form>
				</div>
			</div>
			<div id="bottom" style="padding-bottom: 20px; width: 959px; margin: 0px auto;">
				<p id="title">API Documentation</p>
				<p id="text">Developers can create shortened URLs simply and easily with the <?php echo SITE_NAME ?> API. The API accepts data using the GET method and the result is returned using JSON.</p>
				<div id="tabs">
					<ul>
						<li><a href="#up">Create URL</a></li>
						<li><a href="#down">Get URL</a></li>
					</ul>
					<div id="up">
						<p>Shortened URLs can be created by sending a GET request to the API which is located at <?php echo SITE_URL ?>/api.php. The parameters for the get request are below:</p>
						<table>
							<thead>
								<tr>
									<th>Parameter</th>
									<th>Meaning</th>
									<th>Notes</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>request</td>
									<td>API Request</td>
									<td>create</td>
								</tr>
								<tr>
									<td>url</td>
									<td>Long URL</td>
									<td>Must start with http:// or https://</td>
								</tr>
								<tr>
									<td>key</td>
									<td>API Key</td>
									<td>Optional</td>
								</tr>
							</tbody>
						</table>
						<p>This example shows how to create a short URL for http://www.google.com using a HTTP request using PHP and cURL:</p>
						<pre><code class="php">
&lt;?php

// URL to shorten
$url = "http://www.google.com";

// API Key
$api_key = "<?php echo $api_key ?>";

// Initalize cURL
$ch = curl_init("<?php echo SITE_URL ?>/api.php?request=create&url=".urlencode($url)."&key=".$api_key);

// Don't get header
curl_setopt($ch, CURLOPT_HEADER, false);

// Return output from curl_exec
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Follow Location: headers
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

// Execute
$ret = curl_exec($ch);

if ($ret === false) {
	die("cURL Error: ".curl_error($ch));
}

// Parse result using json_decode()
$json = json_decode($ret);

if ($json->status == "error") {
	die("API request failed. Error: " . $json->message);
}

// Short URL is in the shorturl key
$shorturl = $json->shorturl;

// Output long URL
echo "The short URL for ".$url." is ".$shorturl;
						</code></pre>
						<p>A JSON string is returned from the server when the URL is successfully created, which would like so:</p>
						
<pre><code class="json">
{
	"status": "success",
	"shorturl": "<?php echo SITE_URL ?>/abcdefg",
	"longurl": "http://www.google.com"
}
</code></pre>
						<p>If an error occurred, then the JSON string would something like:</p>
<pre><code class="json">
{
	"status": "error",
	"message": "URL is not valid",
}
</code></pre>
					</div>
					<div id="down">
						<p>Short URLs can be turned into long URLs by sending a GET request to the API which is located at <?php echo SITE_URL ?>/api.php. The parameters for the get request are below:</p>
						<table>
							<thead>
								<tr>
									<th>Parameter</th>
									<th>Meaning</th>
									<th>Notes</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>request</td>
									<td>API Request</td>
									<td>get</td>
								</tr>
								<tr>
									<td>url</td>
									<td>Short URL</td>
									<td>Can be full URL or just path</td>
								</tr>
							</tbody>
						</table>
						<p>This example shows how to get a long URL from a short URL using a HTTP request using PHP and cURL:</p>
						<pre><code class="php">
&lt;?php

// Short URL to get long URL for 
// Can also be "abcdefg"
$url = "<?php echo SITE_URL ?>/abcdefg";

// Initalize cURL
$ch = curl_init("<?php echo SITE_URL ?>/api.php?request=get&url=".urlencode($url));

// Don't get header
curl_setopt($ch, CURLOPT_HEADER, false);

// Return output from curl_exec
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Follow Location: headers
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

// Execute
$ret = curl_exec($ch);

if ($ret === false) {
	die("cURL Error: ".curl_error($ch));
}

// Parse result using json_decode()
$json = json_decode($ret);

if ($json->status == "error") {
	die("API request failed. Error: " . $json->message);
}

// Long URL is in the longurl key
$longurl = $json->longurl;

// Output long URL
echo "The long URL for ".$url." is ".$longurl;
						</code></pre>
						<p>A JSON string is returned from the server when the long URL is successfully retrieved, which would like so:</p>
						
<pre><code class="json">
{
	"status": "success",
	"shorturl": "<?php echo SITE_URL ?>/abcdefg",
	"longurl": "http://www.google.com"
}
</code></pre>
						<p>If an error occurred, then the JSON string would something like:</p>
<pre><code class="json">
{
	"status": "error",
	"message": "URL does not exist",
}
</code></pre>
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