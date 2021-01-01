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

class reputation_class extends AWS_MODEL
{
	// 增加用户赞同数和声望
	public function update_user_agree_count_and_reputation($uid, $agree_count_delta, $reputation_delta)
	{
		$uid = intval($uid);
		if (!$uid OR $uid == -1)
		{
			return false;
		}

		$user_info = $this->model('account')->get_user_info_by_uid($uid);
		if (!$user_info)
		{
			return false;
		}

		$agree_count_delta = intval($agree_count_delta);
		if ($agree_count_delta > 0 AND $user_info['flagged'])
		{
			return false;
		}
		$reputation_delta = floatval($reputation_delta);
		if (is_infinite($reputation_delta))
		{
			$reputation_delta = 0;
		}

		$this->query('UPDATE ' . $this->get_table('users') . ' SET agree_count = agree_count + ' . $agree_count_delta . ', reputation = reputation + ' . $reputation_delta . ' WHERE uid = ' . ($uid));

		// 如果开启了自动封禁功能
		if ($auto_banning_type = get_setting('auto_banning_type'))
		{
			if ($auto_banning_type != 'OFF')
			{
				$agree_count = $user_info['agree_count'] + $agree_count_delta;
				$reputation = $user_info['reputation'] + $reputation_delta;
				$this->model('user')->auto_forbid_user($uid, $user_info['forbidden'], $agree_count, $reputation, $auto_banning_type);
			}
		}

		return true;
	}

}