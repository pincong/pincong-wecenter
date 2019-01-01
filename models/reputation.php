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
	// 通过威望值得到威望组ID
	public function get_reputation_group_id_by_reputation($reputation)
	{
        if ($reputation_groups = $this->model('account')->get_user_group_list(1))
        {
            foreach ($reputation_groups as $key => $val)
            {
                if ((intval($reputation) >= intval($val['reputation_lower'])) AND (intval($reputation) < intval($val['reputation_higer'])))
                {
                    return intval($val['group_id']);
                }
            }
        }
		return 0;
	}

	// 增加用户赞同数和威望
	// 如果满足封禁条件则自动封禁
	public function increase_agree_count_and_reputation($uid, $vote, $reputation_factor)
	{
		$user_info = $this->model('account')->get_user_info_by_uid($uid);
		if (!$user_info)
		{
			return false;
		}

		$agree_count = intval($user_info['agree_count'] + intval($vote));
		$reputation = intval($user_info['reputation'] + intval($vote * $reputation_factor));

		$fields = array(
			'agree_count' => $agree_count,
			'reputation' => $reputation,
			'reputation_update_time' => fake_time()
		);

		$reputation_group = $this->get_reputation_group_id_by_reputation($reputation);
		if ($reputation_group AND $reputation_group != $user_info['reputation_group'])
		{
			$fields['reputation_group'] = $reputation_group;
		}

		// 自动封禁
		if (!$user_info['forbidden'] AND $user_info['group_id'] == 4)
		{
			$auto_banning_agree_count = get_setting('auto_banning_agree_count');
			$auto_banning_reputation = get_setting('auto_banning_reputation');

			if ( ($auto_banning_agree_count !== '' AND $auto_banning_agree_count >= $agree_count)
				OR ($auto_banning_reputation !== '' AND $auto_banning_reputation >= $reputation) )
			{
				$fields['forbidden'] = 1;
			}
		}

		$this->update('users', $fields, 'uid = ' . intval($uid));
	}

	public function calculate_by_uid($uid)
	{
		$user_info = $this->model('account')->get_user_info_by_uid($uid);
		if (!$user_info)
		{
			return false;
		}


		// TODO 根据每个问题/回答/文章/评论的赞同数计算威望
		$reputation = intval($user_info['reputation']);
		$reputation_group = $this->get_reputation_group_id_by_reputation($reputation);

		$fields = array(
			//'agree_count' => $agree_count,
			'reputation' => $reputation,
			'reputation_group' => $reputation_group,
			'reputation_update_time' => fake_time()
		);

		$this->update('users', $fields, 'uid = ' . intval($uid));
	}

	// 重新计算用户威望
	public function calculate($start = 0, $limit = 100)
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
	}

}