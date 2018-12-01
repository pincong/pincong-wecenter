<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2014 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|
+---------------------------------------------------------------------------
*/

if (!defined('IN_ANWSION'))
{
	die;
}

class active_class extends AWS_MODEL
{

	public function calc_user_recovery_code($uid)
	{
		if (! $user_info = $this->fetch_row('users', 'uid = ' . intval($uid)))
		{
			return false;
		}
		return md5(G_SECUKEY . md5($user_info['password'] . $user_info['salt']) . G_COOKIE_HASH_KEY);
	}

	public function verify_user_recovery_code($uid, $recovery_code)
	{
		if (!$code = $this->calc_user_recovery_code($uid))
		{
			return false;
		}
		return ($code == $recovery_code);
	}

	public function active_user_by_uid($uid)
	{
		if (!$uid)
		{
			return false;
		}

		return $this->update('users', array(
			'group_id' => 4,
		), 'uid = ' . intval($uid));
	}

}