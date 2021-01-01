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

	public function verify(&$payload, $token, $secret, $one_time = true)
	{
		$token = json_decode(safe_base64_decode($token), true);
		if (!is_array($token) OR !$token['key'] OR !$token['body'] OR !$token['expire'])
		{
			return false;
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
		if ($one_time)
		{
			if ($this->_get_cache($token['key']))
			{
				return false;
			}
		}
		$body = $this->_decode($token['body'], $secret);
		if (!is_array($body) OR !$body['expire'])
		{
			return false;
		}
		$exipre = intval($body['expire']);
		if ($time >= $exipre)
		{
			return false;
		}
		if ($one_time)
		{
			$this->_set_cache($token['key'], $exipre - $time);
		}
		$payload = $body['payload'];
		return true;
	}

	public function check($token, $secret, $one_time = true)
	{
		return $this->verify($ignore, $token, $secret, $one_time);
	}

	public function create($payload, $expire, $secret)
	{
		$time = time();
		$exipre = $time + intval($expire);
		$body = array(
			'payload' => $payload,
			'expire' => $exipre,
			'time' => $time,
		);
		$body = $this->_encode($body, $secret);
		$token = array(
			'key' => md5($body),
			'body' => $body,
			'expire' => $exipre,
		);
		return safe_base64_encode(json_encode($token));
	}

	public function forget($token)
	{
		$token = json_decode(safe_base64_decode($token), true);
		if (!is_array($token) OR !$token['key'] OR !$token['body'] OR !$token['expire'])
		{
			return;
		}
		$this->_delete_cache($token['key']);
	}
}
