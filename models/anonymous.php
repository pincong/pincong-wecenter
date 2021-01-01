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

class anonymous_class extends AWS_MODEL
{

	public function get_anonymous_uid($user_info = null)
	{
		if ($user_info AND $user_info['permission'])
		{
			$uid = intval($user_info['permission']['anonymous_uid'] ?? null);
		}
		if (!$uid)
		{
			$uid = S::get_int('anonymous_uid');
		}
		if ($uid < 0)
		{
			return -1;
		}
		if ($this->model('account')->uid_exists($uid))
		{
			return $uid;
		}
		// uid 不存在
		return 0;
	}

	public function check_rate_limit($type, $anonymous_uid)
	{
		// TODO
		return true;
	}

	public function check_spam($anonymous_uid)
	{
		// TODO
		return true;
	}


}