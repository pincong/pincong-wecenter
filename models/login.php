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
		$attempts_interval = S::get_int('limit_global_login_attempts_interval') * 60;
		$time_after = real_time() - $attempts_interval;
		$where = [['type', 'eq', 'login'], ['time', 'gte', $time_after]];
		return $this->count('failed_login', $where);
	}

	public function is_captcha_required()
	{
		if (S::get('login_seccode') == 'Y')
		{
			return true;
		}

		if ($max_attempts = S::get_int('limit_global_login_attempts'))
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
	 * @param int
	 * @param string
	 * @return mixed
	 */
	public function verify($uid, $scrambled_password)
	{
		$uid = intval($uid);

		if ($uid <= 0 OR !$scrambled_password)
		{
			return false;
		}

		if (!$user_info = $this->fetch_row('users', ['uid', 'eq', $uid]))
		{
			return false;
		}

		if ($max_attempts = S::get_int('limit_login_attempts'))
		{
			$attempts_interval = S::get_int('limit_login_attempts_interval') * 60;
			$time_after = real_time() - $attempts_interval;

			$where = [['uid', 'eq', $uid], ['type', 'eq', 'login'], ['time', 'gte', $time_after]];
			$failed_login_count = $this->count('failed_login', $where);
			if ($failed_login_count >= $max_attempts)
			{
				return null;
			}
		}

		if (!$this->model('password')->compare($scrambled_password, $user_info['password']))
		{
			$this->log_failed_login($uid);
			// TODO: 给用户发送警告
			return false;
		}

		return $user_info;
	}

	public function cookie_login($uid, $scrambled_password, $expire = null)
	{
		if (!$uid)
		{
			return false;
		}

		if ($expire)
		{
			$expire = time() + $expire;
		}

		AWS_APP::auth()->send_cookie($uid, $scrambled_password, $expire);

		return true;
	}

	public function cookie_logout()
	{
		AWS_APP::auth()->wipe_cookie();
	}

	public function logout()
	{
		AWS_APP::auth()->wipe_cookie();
		AWS_APP::auth()->wipe_token();
	}

}