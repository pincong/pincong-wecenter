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

	public function add_reputation_group($group_name, $reputation_factor = 0, $reputation_lower = 0, $reputation_higer = 0)
	{
		return $this->insert('users_group', array(
			'type' => 1,
			'group_name' => $group_name,
			'reputation_lower' => $reputation_lower,
			'reputation_higer' => $reputation_higer,
			'reputation_factor' => $reputation_factor,
		));
	}

	public function add_system_group($group_name, $reputation_factor = 0)
	{
		return $this->insert('users_group', array(
			'type' => 0,
			'group_name' => $group_name,
			'reputation_lower' => 0,
			'reputation_higer' => 0,
			'reputation_factor' => $reputation_factor,
		));
	}

	public function add_custom_group($group_name, $reputation_factor = 0)
	{
		return $this->insert('users_group', array(
			'type' => 2,
			'group_name' => $group_name,
			'reputation_lower' => 0,
			'reputation_higer' => 0,
			'reputation_factor' => $reputation_factor,
		));
	}

	public function delete_user_group_by_id($group_id)
	{
		$group_id = intval($group_id);

		$this->update('users', array(
			'group_id' => 0,
		), 'group_id = ' . ($group_id));

		if ($group_id <= 2)
		{
			return;
		}

		$this->delete('users_group', 'group_id = ' . ($group_id));
	}

	public function update_user_group_data($group_id, $data)
	{
		return $this->update('users_group', $data, 'group_id = ' . intval($group_id));
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
			if ($group_array = $this->fetch_all('users_group', null, 'type ASC, reputation_lower ASC, group_id ASC'))
			{
				foreach ($group_array as $key => $val)
				{
					$user_group_list[$val['group_id']] = $val;
				}

				AWS_APP::cache()->set('user_group_list', $user_group_list, get_setting('cache_level_normal'), 'users_group');
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
				if ($user_group['permission'])
				{
					$user_group['permission'] = unserialize($user_group['permission']);
				}
				$user_groups[$group_id] = $user_group;
			}
		}

		return $user_group;
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

	public function get_user_group_by_user_info(&$user_info)
	{
		$group_id = intval($user_info['group_id']);
		if (!$group_id)
		{
			$group_id = $this->get_group_id_by_reputation($user_info['reputation']);
		}

		return $this->get_user_group_by_id($group_id);
	}

	// 无缓存版
	public function get_normal_group_list()
	{
		if ($users_groups = $this->fetch_all('users_group', 'type = 0 OR type = 2', 'type ASC, group_id ASC'))
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
		if ($users_groups = $this->fetch_all('users_group', 'type = 1', 'reputation_lower ASC'))
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
		if ($users_groups = $this->fetch_all('users_group', 'type = 0', 'group_id ASC'))
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
		if ($users_groups = $this->fetch_all('users_group', 'type = 2', 'group_id ASC'))
		{
			foreach ($users_groups as $key => $val)
			{
				$groups[$val['group_id']] = $val;
			}
		}
		return $groups;
	}

}
