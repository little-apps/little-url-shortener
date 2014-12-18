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

class ShortURL {
	const SALT = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	
	// Errors
	const ERROR_MISSING_VAR = 'A variable is missing that is required for the function to continue';
	const ERROR_INVALID_URL = 'The specified URL is invalid';
	const ERROR_DB_PREPARE = 'There was an error preparing the SQL statement';
	const ERROR_DB_FETCH = 'There was an error fetching row(s) from the database';
	
	private $db;
	private $user_id = 0;
	private $salt_len;
	private $code;
	private $is_ssl_url = false;
	private $long_url;
	
	/**
	* 
	* @var string If an error occurred, contains error message. Otherwise, it is an empty string
	* 
	*/
	public $error_msg = '';
	
	/**
	* ShortURL class constructor
	*/
	public function __construct() {
		global $mysqli;
		
		$this->db = $mysqli;
		$this->salt_len = strlen( self::SALT );
		
		$this->prepare();
	}
	
	/**
	* Prepares Short URL code
	*/
	private function prepare() {
		$this->generate_code();
		
		try {
			while ($this->code_exists()) {
				$this->generate_code();
			}
		} catch (Exception $e) {
			$this->error_msg = $e->getMessage();
		}
	}
	
	/**
	* Generates code and stores it in $this->code
	*/
	private function generate_code() {
		$this->code = '';
		
		mt_srand(); 

        for ( $i = 0; $i < SITE_SHORTURLLENGTH; $i++ ) { 
            $chr = substr( self::SALT, mt_rand( 0, $this->salt_len - 1 ), 1 ); 
            $this->code .= $chr;
        }
	}
	
	/**
	* Checks if code exists
	* 
	* @return bool Returns true if code already exists, otherwise, false
	*/
	private function code_exists() {
		if (empty($this->code))
			throw new Exception(self::ERROR_MISSING_VAR);
		
		if ($stmt = $this->db->prepare("SELECT COUNT(*) FROM `".MYSQL_PREFIX."urls` WHERE `short_url` = ?")) {
			$stmt->bind_param('s', $this->code);
			$stmt->execute();
			
			$stmt->bind_result($count);
			
			if (!$stmt->fetch()) {
				throw new Exception(self::ERROR_DB_FETCH);
			}
			
			$stmt->close();
		} else {
			throw new Exception(self::ERROR_DB_PREPARE);
		}
		
		return (intval($count) > 0 ? true : false); 
	}
	
	/**
	* Sets user ID to be stored with short URL
	* @param integer $id User ID
	*/
	public function set_user_id($id) {
		if (is_numeric($id))
			$this->user_id = intval($id);
	}
	
	/**
	* Creates short URL
	* @param string $long_url URL for short URL to be linked 
	* 
	* @return bool True if URL was created. Otherwise, it will be false and the error message will be stored in $this->error_msg
	*/
	public function create($long_url) {
		if (!filter_var($long_url, FILTER_VALIDATE_URL)) {
			$this->error_msg = self::ERROR_INVALID_URL;
			return false;
		}
		
		if (!$this->validate_url($long_url)) {
			return false;
		}
		
		$this->long_url = $long_url;
		
		if ($this->insert()) {
			$this->generate_img_token();
			
			return true;
		} else {
			return false;
		}
	}
	
	/**
	* Gets short URL
	* 
	* @return Short URL (with http:// or https://)
	*/
	public function get_short_url() {
		if ($this->is_ssl_url)
			$url = SITE_SSLURL;
		else
			$url = SITE_URL;
			
		$url .= '/' . $this->code;
		
		return $url;
	}
	
	/**
	* Validates long URL
	* @param string $url Long URL
	* 
	* @return True if URL is valid. Otherwise, false and the error message will be stored in $this->error_msg
	*/
	private function validate_url($url) {
		// Parse URL
		$url_parts = parse_url($url);
		
		if ($url_parts === false)
			return false;
		
		// Check scheme
		if (!isset($url_parts['scheme'])) {
			$this->error_msg = 'No URL scheme specified';
			return false;
		} else if (strcasecmp($url_parts['scheme'], 'http') != 0 && strcasecmp($url_parts['scheme'], 'https') != 0) {
			$this->error_msg = 'URL scheme is invalid';
			return false;
		}
		
		// Check host
		if (!isset($url_parts['host'])) {
			$this->error_msg = 'No URL host specified';
			return false;
		}
		
		if ((strlen($url) >= strlen(SITE_SSLURL)) && substr_compare($url, SITE_URL, 0, strlen(SITE_URL), true) == 0 || substr_compare($url, SITE_SSLURL, 0, strlen(SITE_SSLURL), true) == 0) {
			$this->error_msg = 'Cannot shorten URL for another shortened URL';
			return false;
		}
		
		$this->is_ssl_url = ( strcasecmp($url_parts['scheme'], 'https') == 0 ? true : false );
		
		return true;
	}
	
	/**
	* Inserts short URL with long URL into database
	* 
	* @return bool True if it was inserted. Otherwise, false and the error message will be stored in $this->error_msg
	*/
	private function insert() {
		if ($stmt = $this->db->prepare("INSERT INTO `".MYSQL_PREFIX."urls` (`short_url`,`long_url`,`user`,`visits`) VALUES (?,?,?,0)")) {
			$stmt->bind_param('ssi', $this->code, $this->long_url, $this->user_id);
			$stmt->execute();
			
			while ($stmt->affected_rows !== 1) {
				// Code could've been used between it be generated and now
				
				// Regenerate short URL
				$this->generate_code();
				
				$stmt->reset();
				$stmt->execute();
			}
			
			$stmt->close();
			
			return true;
		} else {
			$this->error_msg = self::ERROR_DB_PREPARE;
			return false;
		}
	}
	
	/**
	* Generates and store token for QR code
	*/
	private function generate_img_token() {
		$token = md5(uniqid('image_'));
		
		$_SESSION['image_token'] = $token;
	}
	
	/**
	* Gets the short URL code (not the URL)
	* 
	* @return Short URL code
	*/
	public function __toString() {
        return $this->code;
    }
}

