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
		$rounds = 10;
		return password_hash($string, PASSWORD_BCRYPT, array(
			'cost' => $rounds
		));
	}

	public function check_structure($scrambled_password)
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
		return true;
	}

	public function check_base64_string($str, $max_len)
	{
		if (!$str OR strlen($str) > $max_len)
		{
			return false;
		}

		if (!preg_match('/^([A-Za-z0-9+\/=]+)$/', $str))
		{
			return false;
		}
		return true;
	}

	public function change_password($uid, $scrambled_password, $new_scrambled_password, $new_client_salt, $new_public_key, $new_private_key)
	{
		if (!$user_info = $this->fetch_row('users', ['uid', 'eq', $uid, 'i']))
		{
			return false;
		}

		if (!$this->compare($scrambled_password, $user_info['password']))
		{
			return false;
		}

		return $this->update_password($uid, $new_scrambled_password, $new_client_salt, $new_public_key, $new_private_key);
	}

	public function update_password($uid, $new_scrambled_password, $new_client_salt, $new_public_key, $new_private_key)
	{
		return !!$this->update('users', array(
			'password' => $this->hash($new_scrambled_password),
			'salt' => $new_client_salt,
			'password_version' => 3,
			'public_key' => $new_public_key,
			'private_key' => $new_private_key
		), ['uid', 'eq', $uid, 'i']);
	}

}
