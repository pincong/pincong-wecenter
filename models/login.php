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

class login_class extends AWS_MODEL
{
	// 得到全部用户某段时间内失败登录次数
	public function get_global_failed_login_count()
	{
		$attempts_interval = intval(get_setting('limit_global_login_attempts_interval')) * 60;
		$time_after = real_time() - $attempts_interval;
		$where = 'type = "login" AND time >= ' . $time_after;
		return $this->count('failed_login', $where);
	}

	public function is_captcha_required()
	{
		if (get_setting('login_seccode') == 'Y')
		{
			return true;
		}

		if ($max_attempts = intval(get_setting('limit_global_login_attempts')))
		{
			if ($this->get_global_failed_login_count() >= $max_attempts)
			{
				return true;
			}
		}

		return false;
	}

	public function log_failed_login($uid)
	{
		$this->insert('failed_login', array(
			'type' => 'login',
			'uid' => intval($uid),
			'time' => real_time()
		));
	}

	/**
	 * 用户登录验证
	 * 用户名或密码错误返回 false
	 * 超过尝试次数返回 null
	 *
	 * @param string
	 * @param string
	 * @return mixed
	 */
	public function check_login($user_name, $password)
	{
		if (!$user_name OR !$password)
		{
			return false;
		}

		$user_info = $this->model('account')->get_user_info_by_username($user_name);

		if (!$user_info)
		{
			return false;
		}

		$uid = intval($user_info['uid']);

		if ($max_attempts = intval(get_setting('limit_login_attempts')))
		{
			$attempts_interval = intval(get_setting('limit_login_attempts_interval')) * 60;
			$time_after = real_time() - $attempts_interval;

			$where = 'uid =' . $uid . ' AND type = "login" AND time >= ' . $time_after;
			$failed_login_count = $this->count('failed_login', $where);
			if ($failed_login_count >= $max_attempts)
			{
				return null;
			}
		}

		if (!$this->model('account')->check_password($password, $user_info['password'], $user_info['salt']))
		{
			$this->log_failed_login($uid);
			// TODO: 给用户发送警告
			return false;
		}

		return $user_info;
	}


	public function delete_expired_data()
	{
		$days = intval(get_setting('expiration_failed_login_attempts'));
		if (!$days)
		{
			return;
		}
		$seconds = $days * 24 * 3600;
		$time_before = real_time() - $seconds;
		if ($time_before < 0)
		{
			$time_before = 0;
		}
		$this->delete('failed_login', 'time < ' . $time_before);
	}

}