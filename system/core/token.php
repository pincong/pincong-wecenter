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

class core_token
{
	private function _set_cache($key, $exipre)
	{
		$key = 'core_token_' . $key;
		AWS_APP::cache()->set($key, $exipre, $exipre + 300);
	}

	private function _get_cache($key)
	{
		$key = 'core_token_' . $key;
		return AWS_APP::cache()->get($key);
	}

	private function _delete_cache($key)
	{
		$key = 'core_token_' . $key;
		return AWS_APP::cache()->delete($key);
	}

	private function _encode($body, $secret)
	{
		return bin2hex(AWS_APP::crypt()->encrypt(json_encode($body), $secret));
	}

	private function _decode($body, $secret)
	{
		$body = hex2bin($body);
		if (!$body)
		{
			return false;
		}
		$body = AWS_APP::crypt()->decrypt($body, $secret);
		if (!$body)
		{
			return false;
		}
		return json_decode($body, true);
	}

	public function new_secret($passphrase)
	{
		return AWS_APP::crypt()->new_key($passphrase);
	}

	public function verify(&$payload, $token, $secret, $single_use = true, $start_time = 0)
	{
		$token = json_decode(safe_base64_decode($token), true);
		if (!is_array($token) OR !$token['key'] OR !$token['body'] OR !$token['expire'] OR !$token['time'])
		{
			return false;
		}
		if ($start_time)
		{
			if (intval($token['time']) < $start_time)
			{
				return false;
			}
		}
		$exipre = intval($token['expire']);
		$time = time();
		if ($time >= $exipre)
		{
			return false;
		}
		if ($token['key'] !== md5($token['body']))
		{
			return false;
		}
		if ($this->_get_cache($token['key']))
		{
			return false;
		}
		$body = $this->_decode($token['body'], $secret);
		if (!is_array($body) OR !$body['expire'])
		{
			return false;
		}
		if ($body['expire'] !== $token['expire'] OR $body['time'] !== $token['time'])
		{
			return false;
		}
		if ($single_use)
		{
			$this->_set_cache($token['key'], $exipre - $time);
		}
		$payload = $body['payload'];
		return true;
	}

	public function check($token, $secret, $single_use = true, $start_time = 0)
	{
		return $this->verify($ignore, $token, $secret, $single_use, $start_time);
	}

	public function create($payload, $expire, $secret)
	{
		$time = time();
		$exipre = $time + intval($expire);
		$body = array(
			'payload' => $payload,
			'expire' => $exipre,
			'time' => $time,
			'rand' => rand(-2147483648, 2147483647),
		);
		$body = $this->_encode($body, $secret);
		$token = array(
			'key' => md5($body),
			'body' => $body,
			'expire' => $exipre,
			'time' => $time,
		);
		return safe_base64_encode(json_encode($token));
	}

	public function forget($token)
	{
		$token = json_decode(safe_base64_decode($token), true);
		if (!is_array($token) OR !$token['key'] OR !$token['body'] OR !$token['expire'] OR !$token['time'])
		{
			return;
		}
		$this->_delete_cache($token['key']);
	}
}
