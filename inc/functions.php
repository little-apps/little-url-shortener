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

if (!defined('LUS_LOADED')) die('This file cannot be loaded directly');

/**
* Sends e-mail with PHPMailer class
* 
* @param string $to_email To e-mail
* @param string $to_name To name
* @param string $from_email From e-mail
* @param string $from_name From name
* @param string $subject Subject
* @param string $message Message
*/
function send_email($to_email, $to_name, $from_email, $from_name, $subject, $message) {
	global $php_mailer;
        
	if ( !isset( $php_mailer ) ) {
		require_once( dirname(__FILE__).'/phpmailer/PHPMailerAutoload.php' );

		$php_mailer = new PHPMailer();
	}
	
	if (strcasecmp(MAIL_MAILER, 'smtp') == 0) {
		$php_mailer->isSMTP();
		$php_mailer->Host = SMTP_HOST;
		$php_mailer->Port = intval( SMTP_PORT );
		
		$smtp_user = ( defined('SMTP_USER') ? SMTP_USER : '' );
		$smtp_pass = ( defined('SMTP_PASS') ? SMTP_PASS : '' );

		if (!empty($smtp_user) || !empty($smtp_pass)) {
			$php_mailer->SMTPAuth = true;
			$php_mailer->Username = (!empty($smtp_user) ? $smtp_user : '');
			$php_mailer->Password = (!empty($smtp_pass) ? $smtp_pass : '');
		} else {
			$php_mailer->SMTPAuth = false;
		}
	} else if (strcasecmp(MAIL_MAILER, 'sendmail') == 0) {
		$php_mailer->isSendmail();
		$php_mailer->Sendmail = SENDMAIL_PATH;
	} else {
		$php_mailer->isMail();
	}
	
	$php_mailer->SetFrom($from_email, $from_name);
	$php_mailer->addAddress($to_email, $to_name);
	$php_mailer->Subject = $subject;
	$php_mailer->isHTML(false);
	$php_mailer->Body = $message;
	
	$php_mailer->send();
}

/**
* Outputs Short URL box
*/
function output_short_url() {
	if (isset($_SESSION['short_url'])) { 
?>
		<div class="popup" style="display: none">
			<div class="overlay">&nbsp;</div>
			<div class="inner">
				<div class="content">
					<a href="#" id="closeModal"><img src="images/modal_close.png"/></a>
					<p>Generated URL</p>
					<a href="#" id="short-url"><input id="link" value="<?php echo $_SESSION['short_url'] ?>" readonly /></a>
					<img src="inc/qrcode.php?token=<?php echo $_SESSION['image_token'] ?>" width="256" height="256" alt="QR Code" id="qrcode" />
					<div id="share">
						<button id="facebook" onclick="javascript: window.open('http://www.facebook.com/sharer.php?u=<?php echo urlencode($_SESSION['short_url']) ?>', '_blank');">Share</button>
						<button id="twitter" onclick="javascript: window.open('http://twitter.com/share?url=<?php echo urlencode($_SESSION['short_url']) ?>', '_blank');">Tweet</button>
					</div>
				</div>
			</div>
		</div>
<?php
	}
}

/**
* Ouputs errors box
*/
function output_errors() {
	global $messages;
	
	if ((isset($messages)) && count($messages) > 0) : 
?>
	<div id="message-wrapper">
			<div id="message">
				<ul>
					<li id="closeModal"><a href="#"><img src="<?php echo ( defined('LUS_ADMINAREA') ? '../images/modal_close.png' : 'images/modal_close.png' ); ?>"/></a></li>
					<li id="title">Attention! Please correct the errors below and try again.</li>
					<?php foreach ($messages as $message) : ?>
						<li><?php echo $message; ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
<?php
	endif;
}

/**
* Redirects user to URL (Please note this halts the script)
* 
* @param string $url URL to redirect to
* @param integer $status_code HTTP status code (default: 301)
*/
function redirect($url, $status_code = 301) {
	if (!is_numeric($status_code))
		$status_code = 301;
		
	if (is_string($status_code))
		$status_code = intval($status_code);
		
	if (headers_sent()) {
		// Prevent HTML from breaking
		$url = htmlspecialchars( $url );
		
		echo '<script type="text/javascript">';
        echo 'window.location.href="'.$url.'";';
        echo '</script>';
        echo '<noscript>';
        echo '<meta http-equiv="refresh" content="0;url='.$url.'" />';
        echo '</noscript>';
	} else {	
		if (!function_exists('http_response_code')) {
			// If PHP version is less than 5.4
			header('Location: '.$url, true, $status_code);
		} else {
			http_response_code($status_code);
			header('Location: '.$url);
		}
	}
	
	die();
}

/**
* Outputs title
* 
* @param string $page Page name
*/
function title($page) {
	echo SITE_NAME . ' | ' . $page;
}

/**
* Outputs meta tags
*/
function meta_tags() {
?>
	<meta content="text/html; charset=UTF-8" http-equiv="Content-Type" />
	<meta name="description" content="<?php echo SITE_NAME ?> is a website that allows users to convert their website addresses to a shortened version" />
	<meta name="robots" content="index,follow" />
<?php
}

/**
* Outputs Google Analytics tracking code (if enabled)
*/
function ganalytics_tracking() {
	if ((defined('SITE_GANALYTICS') && defined('SITE_GANALYTICS_ID')) && SITE_GANALYTICS && strlen(SITE_GANALYTICS_ID) > 0) :
?>
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', '<?php echo SITE_GANALYTICS_ID; ?>', 'auto');
  ga('send', 'pageview');

</script>
<?php
	endif;
}