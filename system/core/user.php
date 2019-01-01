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


class core_user
{
	private $info_array;

	public function __construct()
	{
		if (!$_COOKIE[G_COOKIE_PREFIX . '_user_login'])
		{
			// Cookie 清除则 Session 也清除
			$this->clear_session_info();
			return false; // 未登录状态
		}

		if ($this->verify_session_info())
		{
			return true; // 已登录状态
		}

		// 解码 Cookie
		$sso_user_login = json_decode(AWS_APP::crypt()->decode($_COOKIE[G_COOKIE_PREFIX . '_user_login']), true);

		if ($sso_user_login['user_name'] AND $sso_user_login['password'] AND $sso_user_login['uid'])
		{
			if ($user_info = AWS_APP::model('account')->check_hash_login($sso_user_login['user_name'], $sso_user_login['password']))
			{
				$this->set_session_info('uid', $user_info['uid']);
				return true; // 已登录状态
			}
		}

		$this->clear_session_info();
		return false; // 未登录状态
	}

	private function load_info_from_session()
	{
		if (!AWS_APP::session()->client_info)
		{
			$this->info_array = null;
			return false;
		}

		@$this->info_array = unserialize(AWS_APP::crypt()->decode(AWS_APP::session()->client_info));
		if (!is_array($this->info_array))
		{
			$this->info_array = null;
			return false;
		}

		$time_after = time() - 300; // 5分钟内有效
		if (intval($this->info_array['timestamp']) > $time_after)
		{
			return true;
		}

		// 过期
		$this->info_array = null;
		return false;
	}

	public function verify_session_info()
	{
		if ($this->info_array)
		{
			return true;
		}
		return $this->load_info_from_session();
	}

	public function get_session_info($key)
	{
		if (!$this->info_array)
		{
			if (!$this->load_info_from_session())
			{
				return null;
			}
		}
		return $this->info_array[$key];
	}

	public function set_session_info($key, $val)
	{
		if (!$this->info_array)
		{
			$this->info_array = array();
		}
		$this->info_array[$key] = $val;
		$this->info_array['timestamp'] = time();

		AWS_APP::session()->client_info = AWS_APP::crypt()->encode(serialize($this->info_array));
	}

	public function clear_session_info()
	{
		if (isset(AWS_APP::session()->client_info))
		{
			unset(AWS_APP::session()->client_info);
		}
	}
}
