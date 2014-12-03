Little URL Shortener
====================

Little URL Shortener allows users to convert long URLs to short and easy to remember URLs. It is a [Little Apps](https://www.little-apps.com) project and is coded in PHP.

### Requirements ###

The following is required to run Little URL Shortener properly:

* Web server with URL rewrite support (including, but not limited to, [Apache](http://httpd.apache.org/), [LiteSpeed](http://www.litespeedtech.com/) and [Nginx](http://nginx.org))
* [PHP v5.3](http://php.net/) or higher
* [MySQL](http://www.mysql.com/) or [MariaDB](https://www.mariadb.org) v5.5 or higher
* PHP extensions:
 * [MySQL Improved](http://php.net/manual/en/book.mysqli.php)
 * [GD (Image Processing)](http://php.net/manual/en/book.image.php)
 * [Session](http://php.net/manual/en/book.session.php)
 * [JSON](http://php.net/manual/en/book.json.php)

### Installation ###
To install Little URL Shortener, simply navigate to the "install.php" file in your web browser. For example, http://www.mywebsite.com/install.php.

#### Facebook Support ####
For information on how to setup Facebook with Little URL Shortener, please see [this blog post for more details](http://www.little-apps.org/blog/2013/06/using-facebook-little-url-shortener/).

### Example ###

If you would like to see Little URL Shortener in action, please check out [7LS.NET](http://7ls.net). This website is in sync with every update that is pushed to this Git. 

### Release Notes ###
* 1.1
 * Added Facebook support
* 1.0
 * First public release
 
### To Do ###
 * Add update script to update versions
 * Add ability to set HTTP status code for redirect to 301, 302, etc
 * Add ability to enable/disable public API access
 * Fix bug causing users birthday to be set to 1969-12-31 when signed up with Facebook
 * Fix bug that makes users have to login in twice with Facebook

### License ###
Little URL Shortener is licensed under the [GNU General Public License](http://www.gnu.org/licenses/gpl.html). Some scripts that it uses are licensed under [GNU Lesser General Public License](http://www.gnu.org/copyleft/lesser.html) and the [MIT license](http://www.opensource.org/licenses/mit-license.php).

### Show Your Support ###

Little Apps relies on people like you to keep our software running. If you would like to show your support for Little URL Shortener, then you can [make a donation](http://www.little-apps.com/?donate) using PayPal, Payza or Bitcoins. Please note that any amount helps (even just $1).

### Credits ###

Little Apps would like to thank the following for helping Little URL Shortener:

 * [Understanding Hash Functions and Keeping Passwords Safe by Burak G.](http://code.tutsplus.com/tutorials/understanding-hash-functions-and-keeping-passwords-safe--net-17577)
 * [PHPQRCode by Dominik D.](http://phpqrcode.sourceforge.net/)
 * [PHPMailer by Jim J.](https://github.com/PHPMailer/PHPMailer/)
 * [Facebook SDK for PHP by Facebook Inc.](https://developers.facebook.com/docs/reference/php/)
 * [jQuery ZeroClipboard by SteamDev](http://steamdev.com/zclip)
 * [DataTables by Allan Jardine](http://www.datatables.net)
 * [highlight.js by Ivan Sagalaev](https://highlightjs.org/)