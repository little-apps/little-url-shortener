<?php
if (!defined('LUS_LOADED')) die('This file cannot be loaded directly');

class PassHash {  
    /**
	* 
	* @var string Algorithm to use (set to blowfish)
	* 
	*/
    private static $algo = '$2a';

    /**
	* 
	* @var string Cost (A higher number causes the encryption to be stronger but may take longer to generate)
	* 
	*/
    private static $cost = '$10';
    
    /**
	* Generates a 22 character salt from a SHA1 hash
	* 
	* @return string Unique salt
	*/
    public static function unique_salt() {
    	mt_srand();
    	
        return substr(sha1(mt_rand()),0,22);
    }

    /**
	* Generates hash
	* @param string $password Plain text password
	* 
	* @return string Hash
	*/
    public static function hash($password) {
        return crypt($password,
                    self::$algo .
                    self::$cost .
                    '$' . self::unique_salt());

    }

    /**
	* Compares a hash againsta password
	* @param string $hash Hash
	* @param string $password Plain text password
	* 
	* @return bool True if hash matches password. Otherwise, false.
	*/
    public static function check_password($hash, $password) {
        $full_salt = substr($hash, 0, 29);

        $new_hash = crypt($password, $full_salt);

        return ($hash == $new_hash);
    } 
} 