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

	public function get_anonymous_uid()
	{
		$uid = intval(get_setting('anonymous_uid'));
		if ($this->model('account')->uid_exists($uid))
		{
			return $uid;
		}
		// uid 不存在
		return false;
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