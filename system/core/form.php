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

class core_form
{
	private $secret;

	public function __construct()
	{
		$this->secret = AWS_APP::token()->new_secret('form_csrf_passphrase_' . G_COOKIE_HASH_KEY);
	}

	public function create_csrf_token($expire, $type)
	{
		return AWS_APP::token()->create($type, $expire, $this->secret);
	}

	public function check_csrf_token($token, $type, $single_use = true)
	{
		if ($token AND AWS_APP::token()->verify($type_result, $token, $this->secret, $single_use))
		{
			if ($type == $type_result)
			{
				return true;
			}
		}
		return false;
	}

	public function revoke_csrf_token($token)
	{
		AWS_APP::token()->check($token, $this->secret, true);
	}
}
