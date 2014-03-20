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

function send_email($to_email, $to_name, $from_email, $from_name, $subject, $message) {
	require_once(dirname(__FILE__).'/phpmailer/class.phpmailer.php');
	
	$mail = new PHPMailer();
	
	if (strtolower(MAIL_MAILER) == 'smtp') {
		$mail->IsSMTP();
		
		$mail->Host = SMTP_HOST;
		$mail->Port = SMTP_PORT;
		$mail->Secure = SMTP_SECURE;
		$mail->SMTPAuth = true;
		$mail->Username = SMTP_USER;
		$mail->Password = SMTP_PASS;
	} else if (strtolower(MAIL_MAILER) == 'sendmail') {
		$mail->IsSendmail();
		$mail->Sendmail = SENDMAIL_PATH;
	} else {
		$mail->IsMail();
	}
	
	$mail->SetFrom($from_email, $from_name);
	$mail->AddAddress($to_email, $to_name);
	$mail->Subject = $subject;
	$mail->isHTML(false);
	$mail->Body = $message;
	$mail->Send();
}

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

function output_errors() {
	global $messages;
	
	if ((isset($messages)) && count($messages) > 0) : 
?>
	<div id="message-wrapper">
			<div id="message">
				<ul>
					<li id="closeModal"><a href="#"><img src="images/modal_close.png"/></a></li>
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

function title($page) {
	echo SITE_NAME . ' | ' . $page;
}

function meta_tags() {
?>
	<meta content="text/html; charset=UTF-8" http-equiv="Content-Type" />
	<meta name="description" content="<?php echo SITE_NAME ?> is a website that allows users to convert their website addresses to a shortened version" />
	<meta name="robots" content="index,follow" />
<?php
}