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

class usergroup_class extends AWS_MODEL
{

	public function add_reputation_group($group_name, $reputation_factor, $reputation_factor_receive, $content_reputation_factor, $reputation_lower = 0, $reputation_higer = 0)
	{
		if (!is_numeric($reputation_factor))
		{
			$reputation_factor = 0;
		}
		if (!is_numeric($reputation_factor_receive))
		{
			$reputation_factor_receive = null;
		}
		if (!is_numeric($content_reputation_factor))
		{
			$content_reputation_factor = null;
		}

		return $this->insert('users_group', array(
			'type' => 1,
			'group_name' => $group_name,
			'reputation_factor' => $reputation_factor,
			'reputation_factor_receive' => $reputation_factor_receive,
			'content_reputation_factor' => $content_reputation_factor,
			'reputation_lower' => $reputation_lower,
			'reputation_higer' => $reputation_higer,
		));
	}

	public function add_system_group($group_name, $reputation_factor, $reputation_factor_receive, $content_reputation_factor)
	{
		if (!is_numeric($reputation_factor))
		{
			$reputation_factor = 0;
		}
		if (!is_numeric($reputation_factor_receive))
		{
			$reputation_factor_receive = null;
		}
		if (!is_numeric($content_reputation_factor))
		{
			$content_reputation_factor = null;
		}

		return $this->insert('users_group', array(
			'type' => 0,
			'group_name' => $group_name,
			'reputation_factor' => $reputation_factor,
			'reputation_factor_receive' => $reputation_factor_receive,
			'content_reputation_factor' => $content_reputation_factor,
			'reputation_lower' => 0,
			'reputation_higer' => 0,
		));
	}

	public function add_custom_group($group_name, $reputation_factor, $reputation_factor_receive, $content_reputation_factor)
	{
		if (!is_numeric($reputation_factor))
		{
			$reputation_factor = 0;
		}
		if (!is_numeric($reputation_factor_receive))
		{
			$reputation_factor_receive = null;
		}
		if (!is_numeric($content_reputation_factor))
		{
			$content_reputation_factor = null;
		}

		return $this->insert('users_group', array(
			'type' => 2,
			'group_name' => $group_name,
			'reputation_factor' => $reputation_factor,
			'reputation_factor_receive' => $reputation_factor_receive,
			'content_reputation_factor' => $content_reputation_factor,
			'reputation_lower' => 0,
			'reputation_higer' => 0,
		));
	}

	public function delete_user_group_by_id($group_id)
	{
		$group_id = intval($group_id);

		$this->update('users', array(
			'group_id' => 0,
		), ['group_id', 'eq', $group_id]);

		if ($group_id <= 1)
		{
			return;
		}

		$this->delete('users_group', ['group_id', 'eq', $group_id]);
	}

	public function update_user_group_data($group_id, $data)
	{
		if (isset($data['reputation_factor']) AND !is_numeric($data['reputation_factor']))
		{
			$data['reputation_factor'] = 0;
		}
		if (isset($data['reputation_factor_receive']) AND !is_numeric($data['reputation_factor_receive']))
		{
			$data['reputation_factor_receive'] = null;
		}
		if (isset($data['content_reputation_factor']) AND !is_numeric($data['content_reputation_factor']))
		{
			$data['content_reputation_factor'] = null;
		}

		return $this->update('users_group', $data, ['group_id', 'eq', $group_id, 'i']);
	}

	public function get_all_groups()
	{
		static $user_group_list;
		if (!!$user_group_list)
		{
			return $user_group_list;
		}

		if (!$user_group_list = AWS_APP::cache()->get('user_group_list'))
		{
			if ($group_array = $this->fetch_all('users_group', null, 'type ASC, reputation_higer ASC, group_id ASC'))
			{
				foreach ($group_array as $key => $val)
				{
					$user_group_list[$val['group_id']] = $val;
				}

				AWS_APP::cache()->set('user_group_list', $user_group_list, S::get('cache_level_normal'), 'users_group');
			}
		}

		return $user_group_list;
	}


	public function get_user_group_by_id($group_id)
	{
		static $user_groups;

		if (isset($user_groups[$group_id]))
		{
			return $user_groups[$group_id];
		}

		if ($user_group_list = $this->get_all_groups())
		{
			if ($user_group = $user_group_list[$group_id])
			{
				$user_group['permission'] = unserialize_array($user_group['permission']);
				$user_groups[$group_id] = $user_group;
			}
		}

		return $user_group;
	}

	private function check_value_flagged($flagged)
	{
		static $correct_ids;
		if (!isset($correct_ids))
		{
			$correct_ids = S::get('flagged_ids');
			if ($correct_ids)
			{
				$correct_ids = array_map('intval', explode(',', $correct_ids));
			}
			if (!is_array($correct_ids))
			{
				$correct_ids = array();
			}
		}

		if (in_array($flagged, $correct_ids))
		{
			return true;
		}
		return false;
	}

	public function get_groups_flagged()
	{
		static $groups;
		if (isset($groups))
		{
			return $groups;
		}
		$groups = $this->get_all_groups();
		if (!is_array($groups))
		{
			$groups = array();
		}
		foreach ($groups as $key => $val)
		{
			if (!$this->check_value_flagged(intval($val['group_id'])))
			{
				unset($groups[$key]);
			}
		}
		return $groups;
	}

	// 通过'flagged'值得到用户组ID
	public function get_group_id_by_value_flagged($flagged)
	{
		$flagged = intval($flagged);
		if (!$flagged OR !$this->check_value_flagged($flagged))
		{
			return 0;
		}
		if ($all_groups = $this->get_all_groups())
		{
			foreach ($all_groups as $key => $val)
			{
				if ($val['group_id'] == $flagged)
				{
					return $flagged;
				}
			}
		}
		return 0;
	}

	public function get_group_name_by_value_flagged($flagged)
	{
		$group = $this->get_groups_flagged()[intval($flagged)] ?? null;
		return $group ? $group['group_name'] : null;
	}

	// 通过声望值得到用户组ID
	public function get_group_id_by_reputation($reputation)
	{
		if ($all_groups = $this->get_all_groups())
		{
			foreach ($all_groups as $key => $val)
			{
				if ($val['type'] != 1)
				{
					continue;
				}
				if ($reputation >= $val['reputation_lower'] AND $reputation < $val['reputation_higer'])
				{
					return intval($val['group_id']);
				}
			}
		}
		return 0;
	}

	public function get_group_name_by_reputation($reputation)
	{
		if ($all_groups = $this->get_all_groups())
		{
			foreach ($all_groups as $key => $val)
			{
				if ($val['type'] != 1)
				{
					continue;
				}
				if ($reputation >= $val['reputation_lower'] AND $reputation < $val['reputation_higer'])
				{
					return $val['group_name'];
				}
			}
		}
	}

	// 得到声望组或特殊组的名字, 不包括系统组
	public function get_user_group_name_by_user_info($user_info)
	{
		if ($all_groups = $this->get_all_groups())
		{
			foreach ($all_groups as $key => $val)
			{
				if ($val['type'] == 2 AND $val['group_id'] == $user_info['group_id'])
				{
					return $val['group_name'];
				}
			}
		}
		return $this->get_group_name_by_reputation($user_info['reputation']);
	}

	public function get_user_group_by_user_info($user_info)
	{
		// 如果用户被标记（'flagged'），用户组就等于'flagged'值
		$group_id = $this->get_group_id_by_value_flagged($user_info['flagged']);

		if (!$group_id)
		{
			$group_id = intval($user_info['group_id']);
		}

		if (!$group_id) // $group_id 0 表示普通会员
		{
			$group_id = $this->get_group_id_by_reputation($user_info['reputation']);
		}

		return $this->get_user_group_by_id($group_id);
	}

	// 无缓存版
	public function get_normal_group_list()
	{
		if ($users_groups = $this->fetch_all('users_group', [['type', 'eq', 0], 'or', ['type', 'eq', 2]], 'type ASC, group_id ASC'))
		{
			foreach ($users_groups as $key => $val)
			{
				$groups[$val['group_id']] = $val;
			}
		}
		return $groups;
	}

	// 无缓存版
	public function get_reputation_group_list()
	{
		if ($users_groups = $this->fetch_all('users_group', ['type', 'eq', 1], 'reputation_higer ASC, group_id ASC'))
		{
			foreach ($users_groups as $key => $val)
			{
				$groups[$val['group_id']] = $val;
			}
		}
		return $groups;
	}

	// 无缓存版
	public function get_system_group_list()
	{
		if ($users_groups = $this->fetch_all('users_group', ['type', 'eq', 0], 'group_id ASC'))
		{
			foreach ($users_groups as $key => $val)
			{
				$groups[$val['group_id']] = $val;
			}
		}
		return $groups;
	}

	// 无缓存版
	public function get_custom_group_list()
	{
		if ($users_groups = $this->fetch_all('users_group', ['type', 'eq', 2], 'group_id ASC'))
		{
			foreach ($users_groups as $key => $val)
			{
				$groups[$val['group_id']] = $val;
			}
		}
		return $groups;
	}

}
