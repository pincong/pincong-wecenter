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
		$user_name = trim($user_name);

		return $this->fetch_one('users', 'uid', "user_name = '" . $this->quote($user_name) . "'");
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
		return $this->fetch_one('users', 'uid', 'uid = ' . ($uid));
	}


	/**
	 * 通过 UID 获取用户信息（包含用户组和权限信息）
	 *
	 * 缓存结果
	 *
	 * @param int
	 * @return array
	 */
	public function get_user_and_group_info_by_uid($uid)
	{
		$uid = intval($uid);
		if ($uid <= 0)
		{
			return false;
		}

		static $users_info;

		if ($users_info[$uid])
		{
			return $users_info[$uid];
		}

		if (! $user_info = $this->fetch_row('users', 'uid = ' . $uid))
		{
			return false;
		}

		if ($user_info['user_name'])
		{
			$user_info['url_token'] = urlencode($user_info['user_name']);
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
		if ($uid = $this->fetch_one('users', 'uid', "user_name = '" . $this->quote($user_name) . "'"))
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

		if ($users_info[$uid])
		{
			return $users_info[$uid];
		}

		if (! $user_info = $this->fetch_row('users', 'uid = ' . $uid))
		{
			return false;
		}

		if ($user_info['user_name'])
		{
			$user_info['url_token'] = urlencode($user_info['user_name']);
		}

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
		if (! is_array($uids) OR sizeof($uids) == 0)
		{
			return false;
		}

		array_walk_recursive($uids, 'intval_string');

		$uids = array_unique($uids);

		static $users_info;

		if ($users_info[implode('_', $uids)])
		{
			return $users_info[implode('_', $uids)];
		}

		if ($user_info = $this->fetch_all('users', "uid IN(" . implode(',', $uids) . ")"))
		{
			foreach ($user_info as $key => $val)
			{
				$val['url_token'] = urlencode($val['user_name']);

				unset($val['password'], $val['salt']);

				$data[$val['uid']] = $val;
			}

			foreach ($uids AS $uid)
			{
				if ($data[$uid])
				{
					$result[$uid] = $data[$uid];
				}
			}

			$users_info[implode('_', $uids)] = $data;
		}

		return $result;
	}

	/**
	 * 根据用户ID获取用户通知设置
	 * @param $uid
	 */
	public function get_notification_setting_by_uid($uid)
	{
		if (!$setting = $this->fetch_row('users_notification_setting', 'uid = ' . intval($uid)))
		{
			return array('data' => array());
		}

		$setting['data'] = unserialize($setting['data']);

		if (!$setting['data'])
		{
			$setting['data'] = array();
		}

		return $setting;
	}

	/**
	 * 插入用户数据
	 *
	 * @param string
	 * @param string
	 * @param string
	 * @param int
	 * @param string
	 * @return int
	 */
	public function insert_user_deprecated($user_name, $password)
	{
		if (!$user_name OR !$password)
		{
			return false;
		}

		if ($this->username_exists($user_name))
		{
			return false;
		}

		$salt = $this->model('password')->generate_salt_deprecated();
		$password = compile_password($password, $salt);

		if ($uid = $this->insert('users', array(
			'user_name' => htmlspecialchars($user_name),
			'password' => $this->model('password')->hash($password),
			'salt' => $salt,
			'sex' => 0,
			'group_id' => 0,
			'avatar_file' => null, // 无头像
			'reg_time' => fake_time()
		)))
		{
			$this->update_notification_setting_fields(get_setting('new_user_notification_setting'), $uid);

			//$this->model('search_fulltext')->push_index('user', $user_name, $uid);
		}

		return $uid;
	}

	/**
	 * 注册用户
	 *
	 * @param string
	 * @param string
	 * @param string
	 * @return int
	 */
	public function user_register_deprecated($user_name, $password = null)
	{
		if ($uid = $this->insert_user_deprecated($user_name, $password))
		{
			if ($def_focus_uids_str = get_setting('def_focus_uids'))
			{
				$def_focus_uids = explode(',', $def_focus_uids_str);

				foreach ($def_focus_uids as $key => $val)
				{
					$this->model('follow')->user_follow_add($uid, $val);
				}
			}

			$this->model('currency')->process($uid, 'REGISTER', get_setting('currency_system_config_register'), '初始资本');
		}

		return $uid;
	}

	/**
	 * 发送欢迎信息
	 *
	 * @param int
	 * @param string
	 */
	public function welcome_message($uid, $user_name)
	{
		if (get_setting('welcome_message_pm'))
		{
			$this->model('message')->send_message($uid, $uid, str_replace(array('{username}', '{time}', '{sitename}'), array($user_name, date('Y-m-d H:i:s', time()), get_setting('site_name')), get_setting('welcome_message_pm')));
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
		return $this->update('users', $update_data, 'uid = ' . intval($uid));
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
			'user_update_time' => fake_time()
		), 'uid = ' . intval($uid));

		//return $this->model('search_fulltext')->push_index('user', $user_name, $uid);

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
		$user_info = $this->fetch_row('users', 'uid = ' . intval($uid));
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
		$user_info = $this->fetch_row('users', 'uid = ' . intval($uid));
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


	/**
	 * 更新用户最后登录时间
	 *
	 * @param  int
	 */
	public function update_user_last_login($uid)
	{
		if (! $uid)
		{
			return false;
		}

		return $this->update('users', array(
			'last_login' => fake_time()
		), 'uid = ' . intval($uid));
	}

	/**
	 * 更新用户通知设置
	 *
	 * @param  array
	 * @param  int
	 * @return boolean
	 */
	public function update_notification_setting_fields($data, $uid)
	{
		if (!$this->count('users_notification_setting', 'uid = ' . intval($uid)))
		{
			$this->insert('users_notification_setting', array(
				'data' => serialize($data),
				'uid' => intval($uid)
			));
		}
		else
		{
			$this->update('users_notification_setting', array(
				'data' => serialize($data)
			), 'uid = ' . intval($uid));
		}

		return true;
	}

	public function update_notification_unread($uid)
	{
		return $this->update('users', array(
			'notification_unread' => $this->count('notification', 'read_flag = 0 AND recipient_uid = ' . intval($uid))
		), 'uid = ' . intval($uid));
	}

	public function update_question_invite_count($uid)
	{
		return $this->update('users', array(
			'invite_count' => $this->count('question_invite', 'recipients_uid = ' . intval($uid))
		), 'uid = ' . intval($uid));
	}

	public function update_inbox_unread($uid)
	{
		return $this->update('users', array(
			'inbox_unread' => ($this->sum('inbox_dialog', 'sender_unread', 'sender_uid = ' . intval($uid)) + $this->sum('inbox_dialog', 'recipient_unread', 'recipient_uid = ' . intval($uid)))
		), 'uid = ' . intval($uid));
	}

	public function get_user_list($where = null, $limit = 10, $orderby = 'uid DESC')
	{
		$result = $this->fetch_all('users', $where, $orderby, $limit);

		if ($result)
		{
			foreach ($result AS $key => $val)
			{
				unset($val['password'], $val['salt']);

				$data[$val['uid']] = $val;

				if ($val['user_name'])
				{
					$data[$val['uid']]['url_token'] = urlencode($val['user_name']);
				}

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

	public function save_recent_topics($uid, $topic_title)
	{
		if (!$user_info = $this->get_user_info_by_uid($uid))
		{
			return false;
		}

		if ($user_info['recent_topics'])
		{
			$recent_topics = unserialize($user_info['recent_topics']);
		}

		$new_recent_topics[0] = $topic_title;

		if ($recent_topics)
		{
			foreach ($recent_topics AS $key => $val)
			{
				if ($val != $topic_title)
				{
					$new_recent_topics[] = $val;
				}
			}
		}

		if (count($new_recent_topics) > 10)
		{
			$new_recent_topics = array_slice($new_recent_topics, 0, 10);
		}

		return $this->update('users', array(
			'recent_topics' => serialize($new_recent_topics)
		), 'uid = ' . intval($uid));
	}

	public function calc_user_recovery_code($uid)
	{
		if (! $user_info = $this->fetch_row('users', 'uid = ' . intval($uid)))
		{
			return false;
		}
		return md5($user_info['user_name'] . $user_info['uid'] . md5($user_info['password'] . $user_info['salt']) . G_COOKIE_HASH_KEY);
	}

	public function verify_user_recovery_code($uid, $recovery_code)
	{
		if (!$code = $this->calc_user_recovery_code($uid))
		{
			return false;
		}
		return ($code == $recovery_code);
	}

}
