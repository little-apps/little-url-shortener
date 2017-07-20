<?php
if (!defined('LUS_LOADED')) die('This file cannot be loaded directly');

// Website config
define('SITE_URL', 'http://www.example.com');
define('SITE_SSLURL', 'https://www.example.com');
define('SITE_NAME', 'Your site name');
define('SITE_NOREPLY', 'noreply@example.com');
define('SITE_ADMINEMAIL', 'webmaster@example.com');
define('SITE_CONTACTFORM', true);
define('SITE_SHORTURLLENGTH', 7); // Currently, this cannot be greater than 8
define('SITE_VALIDATEIP', true); // If true, sessions are locked to one IP address

define('SITE_GANALYTICS', false); // Set to true to enable Google Analytics tracking
define('SITE_GANALYTICS_ID', ''); // Google Analytics tracking ID (usually something like UA-12345678-12)

// Facebook login
define('FBLOGIN_ENABLED', false); // Set to true to allow Facebook login
define('FBLOGIN_APPID', ''); // Facebook App ID
define('FBLOGIN_APPSECRET', ''); // Facebook App Secret

// Mailer to use (Can be mail, smtp, or sendmail)
// If using SMTP, or sendmail be sure to configure it properly below
define('MAIL_MAILER', 'mail');

// SMTP server info
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 25);
define('SMTP_SECURE', ''); // Can be ssl, tls or blank for none
define('SMTP_USER', 'username');
define('SMTP_PASS', 'password');

// Sendmail path
define('SENDMAIL_PATH', '/usr/sbin/sendmail');

// MySQL config
define('MYSQL_HOST', 'localhost');
define('MYSQL_USER', 'user');
define('MYSQL_PASS', 'pass123');
define('MYSQL_DB', 'database');
define('MYSQL_PREFIX', 'lus_');

// API Configuration
define('API_ENABLE', true);
define('API_READ', true); // Allow short URLs to be translated to long URL using API
define('API_WRITE', true); // Allow short URLS to be created using API
define('API_AUTHORIZED', false); // Requires a valid API key in order to perform API requests