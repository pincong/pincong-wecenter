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

class core_auth
{
	var $PASSPHRASE_LENGTH = 64;
	var $PASSPHRASE_LIFETIME = 1800;
	var $TOKEN_LIFETIME = 600;

	private function _get_passphrase()
	{
		$key = 'core_auth_passphrase';
		$passphrase = AWS_APP::cache()->get($key);
		if (!$passphrase)
		{
			$passphrase = bin2hex(openssl_random_pseudo_bytes($this->PASSPHRASE_LENGTH / 2)) . time();
			AWS_APP::cache()->set($key, $passphrase, $this->PASSPHRASE_LIFETIME);
		}
		return $passphrase;
	}

	private function _create_token($payload)
	{
		$secret = AWS_APP::token()->new_secret($this->_get_passphrase());
		return AWS_APP::token()->create($payload, $this->TOKEN_LIFETIME, $secret);
	}

	private function _get_payload_from_token(&$payload, $token)
	{
		$passphrase = $this->_get_passphrase();
		$passphrase_time = intval(substr($passphrase, $this->PASSPHRASE_LENGTH));
		if (!$passphrase_time)
		{
			return false;
		}
		$secret = AWS_APP::token()->new_secret($passphrase);
		if (AWS_APP::token()->verify($payload, $token, $secret, false, $passphrase_time))
		{
			if (is_array($payload) AND $payload['uid'])
			{
				return true;
			}
		}
		return false;
	}

	private function _get_payload_from_cookie(&$payload, $cookie)
	{
		$payload = AWS_APP::crypt()->decode($cookie);
		if (!$payload)
		{
			return false;
		}
		$payload = json_decode($payload, true);
		if (!is_array($payload) OR !$payload['uid'] OR !$payload['password'])
		{
			return false;
		}
		return true;
	}

	public function authenticate(&$user_info)
	{
		// 首先尝试 token 认证
		if ($token = H::get_cookie('user_token'))
		{
			if ($this->_get_payload_from_token($payload, $token))
			{
				$user_info = AWS_APP::model('account')->get_user_and_group_info_by_uid($payload['uid'], true);
				if ($user_info)
				{
					// token 认证成功
					return true;
				}
			}
			// token 认证失败
			$this->wipe_token();
		}
		// 尝试 cookie 认证
		if ($cookie = H::get_cookie('user_login'))
		{
			if ($this->_get_payload_from_cookie($payload, $cookie))
			{
				$user_info = AWS_APP::model('account')->get_user_and_group_info_by_uid($payload['uid'], true);
				if ($user_info)
				{
					if (AWS_APP::model('password')->compare($payload['password'], $user_info['password']))
					{
						if ($token = $this->_create_token(array('uid' => $payload['uid'])))
						{
							$this->send_token($token);
						}
						// cookie 认证成功
						return true;
					}
				}
			}
			// cookie 认证失败
			$this->wipe_cookie();
		}
		$user_info = null;
		return false;
	}

	public function is_admin()
	{
		if ($token = H::get_cookie('user_token'))
		{
			if ($this->_get_payload_from_token($payload, $token))
			{
				if ($payload['admin'])
				{
					return true;
				}
			}
		}
		return false;
	}

	public function admin_authorize()
	{
		if ($token = H::get_cookie('user_token'))
		{
			if ($this->_get_payload_from_token($payload, $token))
			{
				if ($token = $this->_create_token(array('uid' => $payload['uid'], 'admin' => 1)))
				{
					$this->send_token($token);
					return true;
				}
			}
		}
		return false;
	}

	public function wipe_cookie()
	{
		H::set_cookie('user_login', '', time() - 3600);
	}

	public function wipe_token()
	{
		H::set_cookie('user_token', '', time() - 3600);
	}

	public function send_cookie($uid, $scrambled_password, $expire = null)
	{
		if ($expire)
		{
			$expire = time() + $expire;
		}
		H::set_cookie('user_login', AWS_APP::crypt()->encode(json_encode(array(
			'uid' => $uid,
			'password' => $scrambled_password
		))), $expire);
	}

	public function send_token($token)
	{
		H::set_cookie('user_token', $token);
	}

}
