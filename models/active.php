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

	public function new_find_password($uid, $server = 'master')
	{
		return false;
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