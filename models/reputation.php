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
	public function get_reputation_group_list()
	{
		static $reputation_groups;

		if (!$reputation_groups)
		{
			if ($groups = $this->fetch_all('users_group', 'type = 1'))
			{
				foreach ($groups as $key => $val)
				{
					$reputation_groups[$val['group_id']] = $val;
				}
			}
		}

		return $reputation_groups;
	}

	// 通过威望值得到威望组ID
	public function get_reputation_group_id_by_reputation($reputation)
	{
		if ($reputation_groups = $this->get_reputation_group_list())
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

	// 通过威望值得到威望系数
	public function get_reputation_factor_by_reputation($reputation)
	{
		if ($reputation_groups = $this->get_reputation_group_list())
		{
			foreach ($reputation_groups as $key => $val)
			{
				if ((intval($reputation) >= intval($val['reputation_lower'])) AND (intval($reputation) < intval($val['reputation_higer'])))
				{
					return intval($val['reputation_factor']);
				}
			}
		}
		return 0;
	}

	// 增加用户赞同数和威望
	public function increase_agree_count_and_reputation($uid, $vote, $reputation_factor)
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

		$agree_count_delta = intval($vote);
		$reputation_delta = $agree_count_delta * intval($reputation_factor);

		$this->query('UPDATE ' . $this->get_table('users') . ' SET agree_count = agree_count + ' . $agree_count_delta . ', reputation = reputation + ' . $reputation_delta . ' WHERE uid = ' . ($uid));

		// 如果是普通会员则落实自动封禁功能
		if ($user_info['group_id'] == 4)
		{
			$agree_count = $user_info['agree_count'] + $agree_count_delta;
			$reputation = $user_info['reputation'] + $reputation_delta;
			$this->auto_forbid_user($uid, $user_info['forbidden'], $agree_count, $reputation);
		}

		return true;
	}

	// 如果满足封禁条件则自动封禁
	public function auto_forbid_user($uid, $forbidden, $agree_count, $reputation)
	{
		// 自动封禁/解封, $forbidden == 2 表示已被系统自动封禁
		if (!$forbidden OR $forbidden == 2)
		{
			$auto_banning_agree_count = get_setting('auto_banning_agree_count');
			$auto_banning_reputation = get_setting('auto_banning_reputation');

			if (get_setting('auto_banning_type') == 'AND')
			{
				if ( (is_numeric($auto_banning_agree_count) AND $auto_banning_agree_count >= $agree_count)
					AND (is_numeric($auto_banning_reputation) AND $auto_banning_reputation >= $reputation) )
				{
					if (!$forbidden) // 满足封禁条件且未被封禁的用户
					{
						$fields = array('forbidden' => 2);
					}
				}
				else
				{
					if ($forbidden == 2) // 不满足封禁条件已被封禁的用户
					{
						$fields = array('forbidden' => 0);
					}
				}
			}
			else
			{
				if ( (is_numeric($auto_banning_agree_count) AND $auto_banning_agree_count >= $agree_count)
					OR (is_numeric($auto_banning_reputation) AND $auto_banning_reputation >= $reputation) )
				{
					if (!$forbidden) // 满足封禁条件且未被封禁的用户
					{
						$fields = array('forbidden' => 2);
					}
				}
				else
				{
					if ($forbidden == 2) // 不满足封禁条件已被封禁的用户
					{
						$fields = array('forbidden' => 0);
					}
				}
			}
		}

		if ($fields)
		{
			$this->update('users', $fields, 'uid = ' . intval($uid));
		}
	}

	// 奖励活跃用户
	public function reward_daily_active_users()
	{
		$reputation_above = get_setting('reward_daily_active_users_reputation');
		$bonus = intval(get_setting('reward_daily_active_users_currency'));

		if (!is_numeric($reputation_above) || !$bonus)
		{
			return false;
		}

		$time_after = real_time() - 24 * 3600; // 1天内的活跃用户

		if ($active_users = $this->query_all("SELECT uid FROM " . $this->get_table('users') . " WHERE last_login > " . $time_after . " AND forbidden = 0 AND reputation > " . $reputation_above))
		{
			foreach ($active_users AS $key => $val)
			{
				$this->model('currency')->process($val['uid'], 'DAILY_BONUS', $bonus, '签到奖励');
			}
		}

		return true;
	}


	public function calculate_by_uid($uid)
	{
		// TODO 根据每个问题/回答/文章/评论的赞同数计算威望
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