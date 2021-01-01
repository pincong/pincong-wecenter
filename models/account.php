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

class account_class extends AWS_MODEL
{
	/**
	 * 检查用户名是否已经存在
	 *
	 * @param string
	 * @return boolean
	 */
	public function username_exists($user_name)
	{
		return $this->fetch_one('users', 'uid', ['user_name', 'eq', htmlspecialchars($user_name), 's']);
	}

	/**
	 * 检查用户 ID 是否已经存在
	 *
	 * @param string
	 * @return boolean
	 */

	public function uid_exists($uid)
	{
		$uid = intval($uid);
		if ($uid <= 0)
		{
			return false;
		}
		return $this->fetch_one('users', 'uid', ['uid', 'eq', $uid]);
	}


	/**
	 * 通过 UID 获取用户信息（包含用户组和权限信息）
	 *
	 * 缓存结果
	 *
	 * @param int
	 * @return array
	 */
	public function get_user_and_group_info_by_uid($uid, $get_password = false)
	{
		$uid = intval($uid);
		if ($uid <= 0)
		{
			return false;
		}

		static $users_info;

		if (isset($users_info[$uid]))
		{
			return $users_info[$uid];
		}

		if (!$user_info = $this->fetch_row('users', ['uid', 'eq', $uid]))
		{
			return false;
		}

		if (!$get_password)
		{
			unset($user_info['password'], $user_info['private_key']);
		}

		$user_group = $this->model('usergroup')->get_user_group_by_user_info($user_info);
		$user_info['reputation_factor'] = $user_group['reputation_factor'];
		$user_info['reputation_factor_receive'] = $user_group['reputation_factor_receive'];
		$user_info['content_reputation_factor'] = $user_group['content_reputation_factor'];
		$user_info['permission'] = $user_group['permission'];
		$user_info['user_group_name'] = $user_group['group_name'];
		$user_info['user_group_id'] = $user_group['group_id'];

		$users_info[$uid] = $user_info;

		return $user_info;
	}


	/**
	 * 通过用户名获取用户信息
	 *
	 * 缓存结果
	 *
	 * @param string
	 * @return array
	 */
	public function get_user_info_by_username($user_name)
	{
		if ($uid = $this->fetch_one('users', 'uid', ['user_name', 'eq', htmlspecialchars($user_name), 's']))
		{
			return $this->get_user_info_by_uid($uid);
		}
	}

	/**
	 * 通过 UID 获取用户信息
	 *
	 * 缓存结果
	 *
	 * @param int
	 * @return array
	 */
	public function get_user_info_by_uid($uid)
	{
		$uid = intval($uid);
		if ($uid <= 0)
		{
			return false;
		}

		static $users_info;

		if (isset($users_info[$uid]))
		{
			return $users_info[$uid];
		}

		if (!$user_info = $this->fetch_row('users', ['uid', 'eq', $uid]))
		{
			return false;
		}

		unset($user_info['password'], $user_info['private_key']);

		$users_info[$uid] = $user_info;

		return $user_info;
	}

	/**
	 * 通过 UID 数组获取用户信息
	 *
	 * @param array
	 * @return array
	 */
	public function get_user_info_by_uids($uids)
	{
		if (!is_array($uids) OR sizeof($uids) == 0)
		{
			return false;
		}

		static $users_info;

		if (isset($users_info[implode('_', $uids)]))
		{
			return $users_info[implode('_', $uids)];
		}

		if ($user_info = $this->fetch_all('users', ['uid', 'in', $uids, 'i'], 'uid ASC'))
		{
			foreach ($user_info as $key => $val)
			{
				unset($val['password'], $val['private_key']);

				$data[$val['uid']] = $val;
			}

			foreach ($uids AS $uid)
			{
				if (isset($data[$uid]))
				{
					$result[$uid] = $data[$uid];
				}
			}

			$users_info[implode('_', $uids)] = $data;
		}

		return $result;
	}


	public function get_user_info_by_usernames($usernames)
	{
		if (!is_array($usernames) OR !count($usernames))
		{
			return false;
		}

		foreach ($usernames as &$val)
		{
			$val = htmlspecialchars($val);
		}
		unset($val);

		$result = [];
		if ($users = $this->fetch_all('users', ['user_name', 'in', $usernames, 's'], 'uid ASC'))
		{
			foreach ($users as $val)
			{
				unset($val['password'], $val['private_key']);

				$result[$val['uid']] = $val;
			}
		}

		return $result;
	}


	/**
	 * 发送欢迎信息
	 *
	 * @param int
	 * @param string
	 */
	public function welcome_message($uid, $user_name)
	{
		if (S::get('welcome_message_pm'))
		{
			$message = str_replace(array('{username}', '{time}', '{sitename}'), array($user_name, date('Y-m-d H:i:s', time()), S::get('site_name')), S::get('welcome_message_pm'));
			$message = base64_encode($message);
			$this->model('pm')->notify($uid, $message);
		}
	}

	/**
	 * 更新用户表字段
	 *
	 * @param array
	 * @param uid
	 * @return int
	 */
	public function update_user_fields($update_data, $uid)
	{
		return $this->update('users', $update_data, ['uid', 'eq', $uid, 'i']);
	}

	/**
	 * 更新用户名
	 *
	 * @param string
	 * @param uid
	 */
	public function update_user_name($user_name, $uid)
	{
		$this->update('users', array(
			'user_name' => htmlspecialchars($user_name),
		), ['uid', 'eq', $uid, 'i']);

		return true;
	}

	/**
	 * 获取用户表 extra_data 字段
	 *
	 * @param array
	 * @param uid
	 * @return mixed	成功返回 array, 失败返回 false
	 */
	public function get_user_extra_data($uid)
	{
		$user_info = $this->fetch_row('users', ['uid', 'eq', $uid, 'i']);
		if (!$user_info)
		{
			// uid 不存在
			return false;
		}

		return unserialize_array($user_info['extra_data']);
	}

	/**
	 * 更新用户表 extra_data 字段
	 *
	 * @param array
	 * @param uid
	 * @return void
	 */
	public function update_user_extra_data($data, $uid)
	{
		if (!is_array($data))
		{
			return;
		}

		$extra_data = $this->get_user_extra_data($uid);
		if (!is_array($extra_data))
		{
			return;
		}

		// 覆盖或刪除原有的
		foreach ($data AS $key => $val)
		{
			if ($val === null)
			{
				unset($extra_data[$key]);
			}
			else
			{
				$extra_data[$key] = $val;
			}
		}

		$this->update_user_fields(array('extra_data' => serialize_array($extra_data)), $uid);
	}

	/**
	 * 获取用户表 settings 字段
	 *
	 * @param array
	 * @param uid
	 * @return mixed	成功返回 array, 失败返回 false
	 */
	public function get_user_settings($uid)
	{
		$user_info = $this->fetch_row('users', ['uid', 'eq', $uid, 'i']);
		if (!$user_info)
		{
			// uid 不存在
			return false;
		}

		return unserialize_array($user_info['settings']);
	}

	/**
	 * 更新用户表 settings 字段
	 *
	 * @param array
	 * @param uid
	 * @return void
	 */
	public function update_user_settings($data, $uid)
	{
		if (!is_array($data))
		{
			return;
		}

		$settings = $this->get_user_settings($uid);
		if (!is_array($settings))
		{
			return;
		}

		// 覆盖或刪除原有的
		foreach ($data AS $key => $val)
		{
			if ($val === null)
			{
				unset($settings[$key]);
			}
			else
			{
				$settings[$key] = $val;
			}
		}

		$this->update_user_fields(array('settings' => serialize_array($settings)), $uid);
	}

	public function update_question_invite_count($uid)
	{
		// TODO: 'recipients_uid'改成'recipient_uid'
		return $this->update('users', array(
			'invite_count' => $this->count('question_invite', ['recipients_uid', 'eq', $uid, 'i'])
		), ['uid', 'eq', $uid, 'i']);
	}

	public function get_user_list($where, $order_by, $page, $per_page)
	{
		$result = $this->fetch_page('users', $where, $order_by, $page, $per_page);

		if ($result)
		{
			foreach ($result AS $key => $val)
			{
				unset($val['password'], $val['private_key']);

				$data[$val['uid']] = $val;

				$uids[] = $val['uid'];
			}
		}

		return $data;
	}

	/**
	 * 根据 WHERE 条件获取用户数量
	 *
	 * @param string
	 * @return int
	 */
	public function get_user_count($where = null)
	{
		return $this->count('users', $where);
	}


	public function set_default_timezone($timezone, $uid)
	{
		if (!is_valid_timezone($timezone))
		{
			$timezone = null;
		}
		$this->update_user_settings(array(
			'timezone' => $timezone
		), $uid);
	}

}
