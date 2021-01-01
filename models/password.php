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
		return password_hash($string, PASSWORD_BCRYPT);
	}

	/**
	 * 更改用户密码
	 *
	 * @param  string
	 * @param  string
	 * @param  int
	 * @param  string
	 */
	public function update_user_password($password, $uid, $oldpassword, $salt)
	{
		if (!$salt OR !$uid)
		{
			return false;
		}

		if (! $user_info = $this->fetch_row('users', 'uid = ' . intval($uid)))
		{
			return false;
		}

		$oldpassword = compile_password($oldpassword, $salt);

		if (!$this->compare($oldpassword, $user_info['password']))
		{
			return false;
		}

		return $this->update_user_password_ingore_oldpassword($password, $uid);
	}

	/**
	 * 更改用户不用旧密码密码
	 *
	 * @param  string
	 * @param  int
	 * @param  string
	 */
	public function update_user_password_ingore_oldpassword($password, $uid)
	{
		if (!$password OR !$uid)
		{
			return false;
		}

		$salt = $this->generate_salt_deprecated();

		$this->update('users', array(
			'password' => $this->hash(compile_password($password, $salt)),
			'salt' => $salt
		), 'uid = ' . intval($uid));

		return true;
	}

	public function generate_salt_deprecated()
	{
		$length = 8;
		for ($i = 0; $i < $length; $i++)
		{
			$str .= chr(rand(97, 122));
		}

		return $str;
	}

	public function check_structure($scrambled_password, $client_salt = false)
	{
		if (!$scrambled_password)
		{
			return false;
		}
		if ($client_salt === false)
		{
			return true;
		}
		if (!$client_salt)
		{
			return false;
		}
		return true;
	}

	public function change_password($uid, $scrambled_password, $new_scrambled_password, $new_client_salt = null)
	{
		if (!$user_info = $this->fetch_row('users', 'uid = ' . intval($uid)))
		{
			return false;
		}

		if (!$this->compare($scrambled_password, $user_info['password']))
		{
			return false;
		}

		return $this->update_password($uid, $new_scrambled_password, $new_client_salt);
	}

	public function update_password($uid, $new_scrambled_password, $new_client_salt = null)
	{
		$data = array(
			'password' => $this->hash($new_scrambled_password)
		);

		if (!is_null($new_client_salt))
		{
			$data['salt'] = $new_client_salt;
		}

		$this->update('users', $data, 'uid = ' . intval($uid));

		return true;
	}

}
