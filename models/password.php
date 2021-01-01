<?php
/**
 * WeCenter Framework
 *
 * An open source application development framework for PHP 5.2.2 or newer
 *
 * @package	 WeCenter Framework
 * @author	  WeCenter Dev Team
 * @copyright   Copyright (c) 2011 - 2014, WeCenter, Inc.
 * @license	 http://www.wecenter.com/license/
 * @link		http://www.wecenter.com/
 * @since	   Version 1.0
 * @filesource
 */

/**
 * WeCenter APP 函数类
 *
 * @package	 WeCenter
 * @subpackage  App
 * @category	Model
 * @author	  WeCenter Dev Team
 */


if (!defined('IN_ANWSION'))
{
	die;
}

class password_class extends AWS_MODEL
{
	public function compare($string, $hash)
	{
		return password_verify($string, $hash);
	}

	public function hash($string)
	{
		$rounds = S::get_int('server_side_bcrypt_rounds');
		if (!$rounds)
			$rounds = 10;
		if ($rounds < 4)
			$rounds = 4;
		else if ($rounds > 31)
			$rounds = 31;
		return password_hash($string, PASSWORD_BCRYPT, array(
			'cost' => $rounds
		));
	}

	public function generate_client_salt()
	{
		$rounds = S::get_int('client_side_bcrypt_rounds');
		if (!$rounds)
			$rounds = 10;
		if ($rounds < 4)
			$rounds = 4;
		else if ($rounds > 31)
			$rounds = 31;
		$salt = '$2y$';
		if ($rounds < 10)
			$salt .= '0';
		$salt .= strval($rounds);
		$salt .= '$';
		$bytes = openssl_random_pseudo_bytes(16);
		$salt .= strtr(
			rtrim(base64_encode($bytes), '='),
			'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/',
			'./ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'
		);
		return $salt;
	}

	public function check_structure($scrambled_password, $client_salt = false)
	{
		if (!$scrambled_password OR strlen($scrambled_password) != 60)
		{
			return false;
		}
		$rounds = intval(substr($scrambled_password, 4, 2));
		if ($rounds < 4 OR $rounds > 31)
		{
			return false;
		}
		if (!preg_match('/^(\$2[aby]\$[0-3][0-9]\$)([\.\/A-Za-z0-9]+)$/', $scrambled_password))
		{
			return false;
		}
		if ($client_salt === false)
		{
			return true;
		}
		if (!$client_salt OR strlen($client_salt) != 29)
		{
			return false;
		}
		if (strpos($scrambled_password, $client_salt) !== 0)
		{
			return false;
		}
		return true;
	}

	public function change_password($uid, $scrambled_password, $new_scrambled_password, $new_client_salt)
	{
		if (!$user_info = $this->fetch_row('users', ['uid', 'eq', $uid, 'i']))
		{
			return false;
		}

		if (!$this->compare($scrambled_password, $user_info['password']))
		{
			return false;
		}

		return $this->update_password($uid, $new_scrambled_password, $new_client_salt);
	}

	public function update_password($uid, $new_scrambled_password, $new_client_salt)
	{
		return !!$this->update('users', array(
			'password' => $this->hash($new_scrambled_password),
			'salt' => $new_client_salt,
			'password_version' => 2
		), ['uid', 'eq', $uid, 'i']);
	}

}
