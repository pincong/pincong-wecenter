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
	/*public function calculate_by_uid($uid)
	{
		$this->model('account')->update_users_fields(array(
			'reputation' => round($user_reputation),
			'reputation_update_time' => fake_time()
		), $uid);

		$this->model('account')->update_user_reputation_group($uid);
	}*/

	/*public function calculate($start = 0, $limit = 100)
	{
		if ($users_list = $this->query_all('SELECT uid FROM ' . get_table('users') . ' ORDER BY uid ASC', intval($start) . ',' . intval($limit)))
		{
			foreach ($users_list as $key => $val)
			{
				$this->calculate_by_uid($val['uid']);
			}

			return true;
		}

		return false;
	}*/

}